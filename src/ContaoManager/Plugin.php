<?php
declare(strict_types=1);

namespace Vendor\MembersGridBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\BundleConfigInterface;

class Plugin implements BundlePluginInterface
{
    public function getBundles(array $bundles): array
    {
        return [
            BundleConfig::create(\Vendor\MembersGridBundle\MembersGridBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
