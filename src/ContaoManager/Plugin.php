<?php

namespace Vendor\MembersGridBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Vendor\MembersGridBundle\MembersGridBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(array $bundles): array
    {
        return [
            BundleConfig::create(MembersGridBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
