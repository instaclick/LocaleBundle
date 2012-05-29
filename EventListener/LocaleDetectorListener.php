<?php

namespace Lunetics\LocaleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\HttpFoundation\Cookie;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class for the Locale Detector
 *
 * Detects and sets the Locale
 */
class LocaleDetectorListener implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $availableLanguages = array();

    /**
     * @var null|\Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * Setup the Locale Listener
     *
     * @param string                                                 $defaultLocale      The default Locale
     * @param array                                                  $availableLanguages List of available / allowed locales
     * @param null|\Symfony\Component\HttpKernel\Log\LoggerInterface $logger             Logger Interface
     */
    public function __construct($defaultLocale, $availableLanguages, LoggerInterface $logger = null)
    {
        $this->defaultLocale = $defaultLocale;
        $this->logger = $logger;
        $this->availableLanguages = $availableLanguages;
    }

    /**
     * The Request Listener which sets the locale
     *
     * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        /* @var $request \Symfony\Component\HttpFoundation\Request */

        $session = $request->getSession();
        /* @var $session \Symfony\Component\HttpFoundation\Session */

        if ($session->has('setLocaleCookie') || !$request->cookies->has('locale')) {
            $session->remove('setLocaleCookie');
        }

        // Check if the locale has been identified, no repeating locale checks on subsequent requests needed
        if ($session->has('localeIdentified')) {
            $request->setLocale($session->get('localeIdentified'));

            if (null !== $this->logger) {
                $this->logger->info(sprintf('Locale already Identified : [ %s ]', $session->get('localeIdentified')));
            }

            return;
        }

        // Get the Preferred Language from the Browser
        $preferredLanguage = $request->getPreferredLanguage();
        $providedLanguages = $request->getLanguages();

        if (!$preferredLanguage OR count($providedLanguages) === 0) {
            $preferredLanguage = $this->defaultLocale;
        } else if (!in_array(\Locale::getPrimaryLanguage($preferredLanguage), $this->availableLanguages)) {
            $availableLanguages = $this->availableLanguages;

            $map = function($v) use ($availableLanguages)
            {
                if (in_array(\Locale::getPrimaryLanguage($v), $availableLanguages)) {
                    return true;
                }
            };

            $result = array_values(array_filter($providedLanguages, $map));

            if (!empty($result)) {
                $preferredLanguage = $result[0];
            } else {
                $preferredLanguage = $this->defaultLocale;
            }
        }

        $request->setLocale($preferredLanguage);
        $session->set('localeIdentified', $preferredLanguage);

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Locale detected: [ %s ]', $request->getLocale()));
        }

        return;
    }

    /**
     * The Response Listener which persists the locale
     *
     * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $session = $event->getRequest()->getSession();
        /* @var $session \Symfony\Component\HttpFoundation\Session */

        $response->headers->setCookie(new Cookie('locale', $session->get('localeIdentified')));

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Locale Cookie set to: [ %s ]', $session->get('localeIdentified')));
        }
    }

    static public function getSubscribedEvents()
    {
        return array(
            // must be registered after the Router to have access to the _locale
            KernelEvents::REQUEST  => array(array('onKernelRequest', 8)),
            KernelEvents::RESPONSE => array(array('onKernelResponse', 8))
        );
    }
}