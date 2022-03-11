<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager\Calendar\CalDav;

use AppBundle\Entity\Event;
use AppBundle\Manager\Geo\AddressResolver;
use AppBundle\Manager\Geo\AddressResolverInterface;
use AppBundle\Twig\GlobalCustomization;
use Sabre\VObject\Component\VCalendar;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CalDavVCalendarObjectFactory
{

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @var GlobalCustomization
     */
    private GlobalCustomization $globalCustomization;

    /**
     * app.geo.address_resolver
     *
     * @var AddressResolverInterface
     */
    private AddressResolverInterface $addressResolver;

    /**
     * @param RouterInterface     $router
     * @param GlobalCustomization $globalCustomization
     * @param AddressResolver     $addressResolver
     */
    public function __construct(
        RouterInterface     $router,
        GlobalCustomization $globalCustomization,
        AddressResolver     $addressResolver
    ) {
        $this->router              = $router;
        $this->globalCustomization = $globalCustomization;
        $this->addressResolver     = $addressResolver;
    }


    public function create(): VCalendar
    {
        $vcalendar = new VCalendar
        (
            [
                'VEVENT' => [
                    'CLASS'  => 'PUBLIC',
                    'TRANSP' => 'TRANSPARENT',
                ],
            ]
        );

        if ($this->globalCustomization->organizationName()) {
            if ($this->globalCustomization->organizationEmail()) {
                $vcalendar->VEVENT->add(
                    'ORGANIZER',
                    'MAILTO:' . $this->globalCustomization->organizationEmail(),
                    [
                        'CN' => $this->globalCustomization->organizationName(),
                    ]
                );
            } else {
                $vcalendar->VEVENT->add(
                    'ORGANIZER',
                    $this->globalCustomization->organizationName()
                );
            }
        }

        return $vcalendar;
    }

    /**
     * Create calendar event representing juvem event
     *
     * @param Event $juvemEvent
     * @return VCalendar
     */
    public function createForJuvemEvent(Event $juvemEvent)
    {
        $vcalendar   = $this->create();
        $summary     = $juvemEvent->getTitle();
        $description = $juvemEvent->getDescriptionMeta(true);

        if ($juvemEvent->hasStartTime()) {
            $start = \DateTimeImmutable::createFromFormat(
                'Y-m-d h:i',
                $juvemEvent->getStartDate()->format('Y-m-d') . $juvemEvent->getStartTime()->format('h:i'),
                new \DateTimeZone('Europe/Berlin')
            );
        } else {
            $start = $juvemEvent->getStartDate()->format('Ymd');
        }
        if ($juvemEvent->hasEndDate()) {
            if ($juvemEvent->hasEndTime()) {
                $end = \DateTimeImmutable::createFromFormat(
                    'Y-m-d h:i',
                    $juvemEvent->getEndDate()->format('Y-m-d') . $juvemEvent->getEndTime()->format('h:i'),
                    new \DateTimeZone('Europe/Berlin')
                );
            } else {
                $end = $juvemEvent->getEndDate()->format('Ymd');
            }
        } else {
            if ($juvemEvent->hasEndTime()) {
                $end = \DateTimeImmutable::createFromFormat(
                    'Y-m-d h:i',
                    $juvemEvent->getStartDate()->format('Y-m-d') . $juvemEvent->getEndTime()->format('h:i'),
                    new \DateTimeZone('Europe/Berlin')
                );
            } else {
                $end = $start;
            }
        }

        if (true || $juvemEvent->isShowAddress()) {
            $location = '';
            if ($juvemEvent->getAddressTitle()) {
                $location .= $juvemEvent->getAddressTitle();
            }
            $address = '';
            if ($juvemEvent->getAddressStreet()) {
                $address = $juvemEvent->getAddressStreet();
            }
            if ($juvemEvent->getAddressCity() || $juvemEvent->getAddressZip()) {
                $address .= "\n";
            }
            if ($juvemEvent->getAddressZip()) {
                $address .= $juvemEvent->getAddressZip();
            }
            if ($juvemEvent->getAddressCity()) {
                if ($juvemEvent->getAddressZip()) {
                    $address .= ' ';
                }
                $address .= $juvemEvent->getAddressCity();
            }
            if ($juvemEvent->getAddressCountry()) {
                $address .= ', ' . $juvemEvent->getAddressCountry();
            }
            if ($address) {
                $location .= "\n" . $address;
            }

            $vcalendar->VEVENT->add('LOCATION', $address);

            $coordinates = $this->addressResolver->provideCoordinates($juvemEvent);
            if ($coordinates) {
                $vcalendar->VEVENT->add(
                    'GEO',
                    sprintf(
                        '%1.6F;%1.6F',
                        number_format($coordinates->getLocationLatitude(), 6),
                        number_format($coordinates->getLocationLongitude(), 6),
                    )
                );
                $vcalendar->VEVENT->add(
                    'X-APPLE-STRUCTURED-LOCATION',
                    sprintf(
                        'geo:%1.6F,%1.6F',
                        number_format($coordinates->getLocationLatitude(), 6),
                        number_format($coordinates->getLocationLongitude(), 6),
                    ),
                    [
                        'VALUE'          => 'URI',
                        'X-ADDRESS'      => str_replace("\n", ', ', $address),
                        'X-APPLE-RADIUS' => 49,
                        'X-TITLE'        => $juvemEvent->getAddressTitle(),
                    ]
                );
            }
        }

        $created = $juvemEvent->getCreatedAt();

        $url = $this->router->generate(
            'event_public_detail',
            [
                'eid' => $juvemEvent->getEid(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $vcalendar->VEVENT->add('SUMMARY', $summary);
        $vcalendar->VEVENT->add('DESCRIPTION', $description);
        $vcalendar->VEVENT->add('DTSTAMP', $created);

        if (is_string($start)) {
            $vcalendar->VEVENT->add(
                'DTSTART',
                $start,
                [
                    'VALUE' => 'DATE',
                ]
            );
        } else {
            $vcalendar->VEVENT->add(
                'DTSTART',
                $start
            );
        }
        if (is_string($end)) {
            $vcalendar->VEVENT->add(
                'DTEND',
                $end,
                [
                    'VALUE' => 'DATE',
                ]
            );
        } else {
            $vcalendar->VEVENT->add(
                'DTEND',
                $end
            );
        }

        if ($url) {
            $vcalendar->VEVENT->add(
                'URL',
                $url
            );
        }

        return $vcalendar;
    }
}
