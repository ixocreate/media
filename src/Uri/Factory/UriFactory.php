<?php
/**
 * @see https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Media\Uri\Factory;

use Ixocreate\Contract\ServiceManager\FactoryInterface;
use Ixocreate\Contract\ServiceManager\ServiceManagerInterface;
use Ixocreate\Media\Config\MediaConfig;
use Ixocreate\Media\Uri\Uri;
use Ixocreate\ProjectUri\ProjectUri;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

final class UriFactory implements FactoryInterface
{
    /**
     * @param ServiceManagerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return Uri|mixed
     */
    public function __invoke(ServiceManagerInterface $container, $requestedName, array $options = null)
    {
        $packages = new Packages();

        $urlPackage = new UrlPackage(
            (string) $container->get(MediaConfig::class)->uri(),
            new EmptyVersionStrategy()
        );
        $packages->setDefaultPackage($urlPackage);

        $urlPackage = new UrlPackage(
            (string) $container->get(ProjectUri::class)->getMainUrl() . '/media/stream/',
            new EmptyVersionStrategy()
        );
        $packages->addPackage('streamMedia', $urlPackage);

        return new Uri($packages);
    }
}
