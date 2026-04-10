# Members Grid (Contao)

Reusable Contao **content element** that renders a 4-person **members cutout grid** (left / top / right / bottom).  
Designed to be **theme-agnostic**: the bundle outputs clean HTML hooks, styling is handled in your project CSS.



## Features

- Content element: **Members grid**
- 4 slots
- Image (UUID via fileTree)
- Caption/name (text)

## Template
ce_members_grid.html5

## Installation (via Composer / Contao Manager)

Add the repository to your **Contao project** `composer.json` (root of the Contao installation):

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/vtxm-h/members-grid"
    }
  ]
}
```
Installation
```json
composer require vtxm-h/members-grid
```



Gut. Dann ziehen wir das konsequent im Stil eurer bestehenden Bundles durch, aber mit:

    Content Element

    DCA in tl_content

    Template-Name nicht ce_*, sondern layout_preset

Also:

    technisch wie members-grid

    Benennung eher wie article-insert

Finaler Startpunkt für das neue Bundle
Paketname

"vtxm-h/layout-preset"

Namespace

Vendor\LayoutPresetBundle

Bundle-Klasse

LayoutPresetBundle

CE-Typ

layout_preset

Template

layout_preset.html5

1) Ordnerstruktur

layout-preset/
├── composer.json
└── src/
    ├── LayoutPresetBundle.php
    ├── ContaoManager/
    │   └── Plugin.php
    ├── ContentElement/
    │   └── LayoutPresetElement.php
    ├── Dca/
    │   └── LayoutPresetOptions.php
    └── Resources/
        ├── config/
        │   └── services.yaml
        └── contao/
            ├── config/
            │   └── config.php
            ├── dca/
            │   └── tl_content.php
            ├── languages/
            │   ├── de/
            │   │   └── tl_content.php
            │   └── en/
            │       └── tl_content.php
            └── templates/
                └── layout_preset.html5

2) composer.json

{
  "name": "vtxm-h/layout-preset",
  "description": "Contao 4.13 content element: layout preset with article, module or html slots.",
  "type": "contao-bundle",
  "license": "MIT",
  "require": {
    "php": "^8.0",
    "contao/core-bundle": "^4.13",
    "contao/manager-plugin": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\LayoutPresetBundle\\": "src/"
    }
  },
  "extra": {
    "contao-manager-plugin": "Vendor\\LayoutPresetBundle\\ContaoManager\\Plugin"
  }
}

3) src/LayoutPresetBundle.php

<?php

namespace Vendor\LayoutPresetBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LayoutPresetBundle extends Bundle
{
}

4) src/ContaoManager/Plugin.php

<?php

namespace Vendor\LayoutPresetBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Vendor\LayoutPresetBundle\LayoutPresetBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(LayoutPresetBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}

5) src/Resources/contao/config/config.php

Registrierung des Content Elements.

<?php

$GLOBALS['TL_CTE']['vtxm']['layout_preset']
    = \Vendor\LayoutPresetBundle\ContentElement\LayoutPresetElement::class;

6) src/Resources/config/services.yaml

services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Vendor\LayoutPresetBundle\:
    resource: '../../*'
    exclude: '../../{Resources,ContaoManager,DependencyInjection,Entity,Tests}'

7) src/ContentElement/LayoutPresetElement.php

Hier jetzt mit Template-Name layout_preset.

<?php

namespace Vendor\LayoutPresetBundle\ContentElement;

use Contao\ArticleModel;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\Controller;
use Contao\ModuleModel;
use Contao\StringUtil;

class LayoutPresetElement extends ContentElement
{
    protected $strTemplate = 'layout_preset';

    protected function compile(): void
    {
        $this->Template->layoutPreset      = (string) $this->layoutPreset;
        $this->Template->layoutMode        = (string) $this->layoutMode;
        $this->Template->layoutAlign       = (string) $this->layoutAlign;
        $this->Template->layoutDivider     = (bool) $this->layoutDivider;
        $this->Template->layoutStackMobile = (bool) $this->layoutStackMobile;

        $areaA = $this->resolveSlot(
            (string) $this->slotAType,
            (int) $this->slotAArticle,
            (int) $this->slotAModule,
            (string) $this->slotAHtml
        );

        $areaB = $this->resolveSlot(
            (string) $this->slotBType,
            (int) $this->slotBArticle,
            (int) $this->slotBModule,
            (string) $this->slotBHtml
        );

        $this->Template->areaA = $areaA;
        $this->Template->areaB = $areaB;

        $this->Template->hasAreaA = trim((string) $areaA) !== '';
        $this->Template->hasAreaB = trim((string) $areaB) !== '';

        $this->Template->renderFirst = \in_array($this->layoutMode, ['right-left', 'bottom-top'], true)
            ? 'B'
            : 'A';
    }

    protected function resolveSlot(string $type, int $articleId, int $moduleId, string $html): string
    {
        switch ($type) {
            case 'article':
                return $this->renderArticle($articleId);

            case 'module':
                return $this->renderModule($moduleId);

            case 'html':
                return StringUtil::decodeEntities($html);
        }

        return '';
    }

    protected function renderArticle(int $articleId): string
    {
        if ($articleId <= 0) {
            return '';
        }

        $article = ArticleModel::findByPk($articleId);

        if (null === $article) {
            return '';
        }

        $elements = ContentModel::findPublishedByPidAndTable($article->id, 'tl_article');
        $buffer = '';

        if (null !== $elements) {
            while ($elements->next()) {
                $buffer .= $this->getContentElement($elements->id);
            }
        }

        return $buffer;
    }

    protected function renderModule(int $moduleId): string
    {
        if ($moduleId <= 0) {
            return '';
        }

        $module = ModuleModel::findByPk($moduleId);

        if (null === $module) {
            return '';
        }

        return Controller::getFrontendModule($moduleId);
    }
}

8) src/Dca/LayoutPresetOptions.php

Für die Artikelauswahl – im Stil eurer bisherigen Bundle-Helfer.

<?php

namespace Vendor\LayoutPresetBundle\Dca;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Database;

class LayoutPresetOptions extends Backend
{
    public function getArticlesForSlotA(DataContainer $dc): array
    {
        return $this->getArticlesByPageField($dc, 'slotAPage');
    }

    public function getArticlesForSlotB(DataContainer $dc): array
    {
        return $this->getArticlesByPageField($dc, 'slotBPage');
    }

    protected function getArticlesByPageField(DataContainer $dc, string $field): array
    {
        if (!$dc->activeRecord || !$dc->activeRecord->{$field}) {
            return [];
        }

        $stmt = Database::getInstance()
            ->prepare('SELECT id, title, inColumn FROM tl_article WHERE pid=? ORDER BY sorting')
            ->execute((int) $dc->activeRecord->{$field});

        $options = [];

        while ($stmt->next()) {
            $label = $stmt->title;

            if ($stmt->inColumn) {
                $label .= ' [' . $stmt->inColumn . ']';
            }

            $options[(int) $stmt->id] = $label;
        }

        return $options;
    }
}

9) src/Resources/contao/templates/layout_preset.html5

<?php

$classes = [
    'layout-preset',
    'preset--' . $this->layoutPreset,
    'mode--' . $this->layoutMode,
    'align--' . $this->layoutAlign,
];

if ($this->layoutDivider) {
    $classes[] = 'has-divider';
}

if ($this->layoutStackMobile) {
    $classes[] = 'is-stack-mobile';
}
?>

<div class="<?= implode(' ', $classes) ?>">
    <div class="layout-preset__inner">

        <?php if ($this->renderFirst === 'A'): ?>
            <?php if ($this->hasAreaA): ?>
                <div class="layout-preset__area layout-preset__area--a">
                    <?= $this->areaA ?>
                </div>
            <?php endif; ?>

            <?php if ($this->hasAreaB): ?>
                <div class="layout-preset__area layout-preset__area--b">
                    <?= $this->areaB ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($this->hasAreaB): ?>
                <div class="layout-preset__area layout-preset__area--b">
                    <?= $this->areaB ?>
                </div>
            <?php endif; ?>

            <?php if ($this->hasAreaA): ?>
                <div class="layout-preset__area layout-preset__area--a">
                    <?= $this->areaA ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

10) src/Resources/contao/dca/tl_content.php

Jetzt passend zur finalen Logik.

<?php

use Vendor\LayoutPresetBundle\Dca\LayoutPresetOptions;

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'slotAType';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'slotBType';

$GLOBALS['TL_DCA']['tl_content']['palettes']['layout_preset']
    = '{type_legend},type,headline;'
    . '{layout_legend},layoutPreset,layoutMode,layoutAlign,layoutDivider,layoutStackMobile;'
    . '{slot_a_legend},slotAType;'
    . '{slot_b_legend},slotBType;'
    . '{protected_legend:hide},protected;'
    . '{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotAType_article'] = 'slotAPage,slotAArticle';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotAType_module']  = 'slotAModule';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotAType_html']    = 'slotAHtml';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotBType_article'] = 'slotBPage,slotBArticle';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotBType_module']  = 'slotBModule';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['slotBType_html']    = 'slotBHtml';

$GLOBALS['TL_DCA']['tl_content']['fields']['layoutPreset'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['layoutPreset'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['about', 'contact', 'spotlight', 'default'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'default'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['layoutMode'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['layoutMode'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['left-right', 'right-left', 'top-bottom', 'bottom-top'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'left-right'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['layoutAlign'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['layoutAlign'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['start', 'center'],
    'eval'      => [
        'chosen'   => true,
        'tl_class' => 'w50',
    ],
    'sql'       => "varchar(16) NOT NULL default 'start'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['layoutDivider'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['layoutDivider'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50 m12',
    ],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['layoutStackMobile'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['layoutStackMobile'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 m12',
    ],
    'sql'       => "char(1) NOT NULL default '1'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotAType'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotAType'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['article', 'module', 'html'],
    'eval'      => [
        'mandatory'      => true,
        'chosen'         => true,
        'submitOnChange' => true,
        'tl_class'       => 'w50 clr',
    ],
    'sql'       => "varchar(16) NOT NULL default 'article'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotBType'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotBType'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['article', 'module', 'html'],
    'eval'      => [
        'mandatory'      => true,
        'chosen'         => true,
        'submitOnChange' => true,
        'tl_class'       => 'w50 clr',
    ],
    'sql'       => "varchar(16) NOT NULL default 'article'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotAPage'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotAPage'],
    'exclude'   => true,
    'inputType' => 'pageTree',
    'eval'      => [
        'mandatory'      => true,
        'fieldType'      => 'radio',
        'submitOnChange' => true,
        'tl_class'       => 'w50 clr',
    ],
    'sql'       => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotAArticle'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_content']['slotAArticle'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => [LayoutPresetOptions::class, 'getArticlesForSlotA'],
    'eval'             => [
        'mandatory'          => true,
        'chosen'             => true,
        'includeBlankOption' => true,
        'tl_class'           => 'w50',
    ],
    'sql'              => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotBPage'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotBPage'],
    'exclude'   => true,
    'inputType' => 'pageTree',
    'eval'      => [
        'mandatory'      => true,
        'fieldType'      => 'radio',
        'submitOnChange' => true,
        'tl_class'       => 'w50 clr',
    ],
    'sql'       => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotBArticle'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_content']['slotBArticle'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => [LayoutPresetOptions::class, 'getArticlesForSlotB'],
    'eval'             => [
        'mandatory'          => true,
        'chosen'             => true,
        'includeBlankOption' => true,
        'tl_class'           => 'w50',
    ],
    'sql'              => "int(10) unsigned NOT NULL default 0",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotAModule'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_content']['slotAModule'],
    'exclude'    => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_module.name',
    'eval'       => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50 clr',
    ],
    'sql'        => "int(10) unsigned NOT NULL default 0",
    'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotBModule'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_content']['slotBModule'],
    'exclude'    => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_module.name',
    'eval'       => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50 clr',
    ],
    'sql'        => "int(10) unsigned NOT NULL default 0",
    'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotAHtml'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotAHtml'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => [
        'allowHtml' => true,
        'rte'       => 'tinyMCE',
        'tl_class'  => 'clr',
    ],
    'sql'       => "mediumtext NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['slotBHtml'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['slotBHtml'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => [
        'allowHtml' => true,
        'rte'       => 'tinyMCE',
        'tl_class'  => 'clr',
    ],
    'sql'       => "mediumtext NULL",
];

11) src/Resources/contao/languages/de/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['layout_preset'] = ['Layout Preset', 'Layout-Wrapper mit zwei frei befüllbaren Slots.'];

$GLOBALS['TL_LANG']['tl_content']['layout_legend'] = 'Layout';
$GLOBALS['TL_LANG']['tl_content']['slot_a_legend'] = 'Slot A';
$GLOBALS['TL_LANG']['tl_content']['slot_b_legend'] = 'Slot B';

$GLOBALS['TL_LANG']['tl_content']['layoutPreset'] = ['Preset', 'Layout-Preset wählen.'];
$GLOBALS['TL_LANG']['tl_content']['layoutMode'] = ['Anordnung', 'Anordnung der beiden Bereiche.'];
$GLOBALS['TL_LANG']['tl_content']['layoutAlign'] = ['Vertikale Ausrichtung', 'Vertikale Ausrichtung der Bereiche.'];
$GLOBALS['TL_LANG']['tl_content']['layoutDivider'] = ['Divider anzeigen', 'Zeigt einen visuellen Trenner zwischen den Bereichen.'];
$GLOBALS['TL_LANG']['tl_content']['layoutStackMobile'] = ['Auf Mobile stapeln', 'Die Bereiche auf kleinen Viewports untereinander anzeigen.'];

$GLOBALS['TL_LANG']['tl_content']['slotAType'] = ['Slot A Typ', 'Quelle für Slot A.'];
$GLOBALS['TL_LANG']['tl_content']['slotAPage'] = ['Slot A Seite', 'Seite auswählen, aus der der Artikel geladen wird.'];
$GLOBALS['TL_LANG']['tl_content']['slotAArticle'] = ['Slot A Artikel', 'Artikel auswählen.'];
$GLOBALS['TL_LANG']['tl_content']['slotAModule'] = ['Slot A Modul', 'Frontend-Modul auswählen.'];
$GLOBALS['TL_LANG']['tl_content']['slotAHtml'] = ['Slot A HTML', 'Freier HTML-/RTE-Inhalt für Slot A.'];

$GLOBALS['TL_LANG']['tl_content']['slotBType'] = ['Slot B Typ', 'Quelle für Slot B.'];
$GLOBALS['TL_LANG']['tl_content']['slotBPage'] = ['Slot B Seite', 'Seite auswählen, aus der der Artikel geladen wird.'];
$GLOBALS['TL_LANG']['tl_content']['slotBArticle'] = ['Slot B Artikel', 'Artikel auswählen.'];
$GLOBALS['TL_LANG']['tl_content']['slotBModule'] = ['Slot B Modul', 'Frontend-Modul auswählen.'];
$GLOBALS['TL_LANG']['tl_content']['slotBHtml'] = ['Slot B HTML', 'Freier HTML-/RTE-Inhalt für Slot B.'];

12) src/Resources/contao/languages/en/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['layout_preset'] = ['Layout Preset', 'Layout wrapper with two configurable slots.'];

$GLOBALS['TL_LANG']['tl_content']['layout_legend'] = 'Layout';
$GLOBALS['TL_LANG']['tl_content']['slot_a_legend'] = 'Slot A';
$GLOBALS['TL_LANG']['tl_content']['slot_b_legend'] = 'Slot B';

$GLOBALS['TL_LANG']['tl_content']['layoutPreset'] = ['Preset', 'Select a layout preset.'];
$GLOBALS['TL_LANG']['tl_content']['layoutMode'] = ['Layout mode', 'Arrangement of the two areas.'];
$GLOBALS['TL_LANG']['tl_content']['layoutAlign'] = ['Vertical alignment', 'Vertical alignment of both areas.'];
$GLOBALS['TL_LANG']['tl_content']['layoutDivider'] = ['Show divider', 'Show a visual divider between the areas.'];
$GLOBALS['TL_LANG']['tl_content']['layoutStackMobile'] = ['Stack on mobile', 'Stack both areas on small screens.'];

$GLOBALS['TL_LANG']['tl_content']['slotAType'] = ['Slot A type', 'Source for slot A.'];
$GLOBALS['TL_LANG']['tl_content']['slotAPage'] = ['Slot A page', 'Select the page to load the article from.'];
$GLOBALS['TL_LANG']['tl_content']['slotAArticle'] = ['Slot A article', 'Select the article.'];
$GLOBALS['TL_LANG']['tl_content']['slotAModule'] = ['Slot A module', 'Select the frontend module.'];
$GLOBALS['TL_LANG']['tl_content']['slotAHtml'] = ['Slot A HTML', 'Free HTML/RTE content for slot A.'];

$GLOBALS['TL_LANG']['tl_content']['slotBType'] = ['Slot B type', 'Source for slot B.'];
$GLOBALS['TL_LANG']['tl_content']['slotBPage'] = ['Slot B page', 'Select the page to load the article from.'];
$GLOBALS['TL_LANG']['tl_content']['slotBArticle'] = ['Slot B article', 'Select the article.'];
$GLOBALS['TL_LANG']['tl_content']['slotBModule'] = ['Slot B module', 'Select the frontend module.'];
$GLOBALS['TL_LANG']['tl_content']['slotBHtml'] = ['Slot B HTML', 'Free HTML/RTE content for slot B.'];

13) Ergebnis / Stiltreue

Damit ist das neue Bundle jetzt:

    formal wie members-grid

    im Rendering logisch nah an article-insert

    mit freierer Template-Benennung wie von dir gewünscht

Also genau die Mischform, die zu euren bestehenden Repos passt.
14) Nächster logischer Schritt

Jetzt macht es Sinn, direkt als nächstes das zweite Bundle im selben Stil zu definieren:
tabs

    ebenfalls als Content Element

    ebenfalls ohne ce_*-Template-Namenszwang

    gleiche Struktur / gleiche Bundle-Konvention

Wenn du willst, mache ich jetzt direkt tabs im exakt selben Schema:

    Repo

    Struktur

    composer.json

    DCA

    Klasse

    Template

    minimale JS-Hooks.


Moment... ich muss das komplette Zeug jetzt erstmal anlegen auf der github seite

Ja. Mach erst das Repo sauber auf.

Die sinnvolle Reihenfolge ist:

    Neues GitHub-Repo anlegen

        layout-preset

    Grundstruktur lokal anlegen

        composer.json

        src/

        Resources/...

    Erst die Minimaldateien rein

        composer.json

        LayoutPresetBundle.php

        Plugin.php

        config.php

        services.yaml

    Dann committen

        damit Namespace, Autoload und Bundle-Struktur schon sauber stehen

    Erst danach

        DCA

        CE-Klasse

        Template

        Sprachdateien

Das ist besser, weil du Fehler früher siehst und nicht gleich 10 Dateien debuggen musst.

Wenn du soweit bist, gehen wir Datei für Datei weiter.

Wenn du willst, mache ich jetzt direkt tabs im exakt selben Schema

-> lets go

Gut. Dann jetzt tabs im exakt selben Schema wie eben bei layout-preset:

    Bundle-Struktur wie members-grid

    Template-Benennung frei wie bei article-insert

    technisch ein Content Element

    Contao 4.13 kompatibel

    V1 bewusst schlank

Ziel
Repo

tabs

Composer-Paket

"vtxm-h/tabs"

Namespace

Vendor\TabsBundle

CE-Typ

tabs

Template

tabs.html5

1) Ordnerstruktur

tabs/
├── composer.json
└── src/
    ├── TabsBundle.php
    ├── ContaoManager/
    │   └── Plugin.php
    ├── ContentElement/
    │   └── TabsElement.php
    └── Resources/
        ├── config/
        │   └── services.yaml
        └── contao/
            ├── config/
            │   └── config.php
            ├── dca/
            │   └── tl_content.php
            ├── languages/
            │   ├── de/
            │   │   └── tl_content.php
            │   └── en/
            │       └── tl_content.php
            └── templates/
                └── tabs.html5

2) composer.json

{
  "name": "vtxm-h/tabs",
  "description": "Contao 4.13 content element: tabs with repeatable items.",
  "type": "contao-bundle",
  "license": "MIT",
  "require": {
    "php": "^8.0",
    "contao/core-bundle": "^4.13",
    "contao/manager-plugin": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\TabsBundle\\": "src/"
    }
  },
  "extra": {
    "contao-manager-plugin": "Vendor\\TabsBundle\\ContaoManager\\Plugin"
  }
}

3) src/TabsBundle.php

<?php

namespace Vendor\TabsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TabsBundle extends Bundle
{
}

4) src/ContaoManager/Plugin.php

<?php

namespace Vendor\TabsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Vendor\TabsBundle\TabsBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(TabsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}

5) src/Resources/contao/config/config.php

<?php

$GLOBALS['TL_CTE']['vtxm']['tabs']
    = \Vendor\TabsBundle\ContentElement\TabsElement::class;

6) src/Resources/config/services.yaml

services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Vendor\TabsBundle\:
    resource: '../../*'
    exclude: '../../{Resources,ContaoManager,DependencyInjection,Entity,Tests}'

7) V1-Datenmodell

Für V1 würde ich kein verschachteltes Monster bauen, sondern:
Basisfelder

    tabsStyle

        default

        pills

        underline

    tabsLayout

        horizontal

        stack-mobile

    tabsItems

        Repeater mit:

            label

            content

8) Wichtiger Punkt: Repeater in Contao

Du brauchst für echte Repeater in Contao in der Praxis meist:

    multiColumnWizard

    oder eine eigene Child-Table

    oder ein JSON-Feld mit eigener Widget-Lösung

Für V1 ist der pragmatische Weg:
Entweder:
A) multiColumnWizard verwenden

wenn du die Erweiterung eh schon standardisiert einsetzt

oder
B) fürs erste ein JSON-/Textarea-Feld

und du pflegst strukturiert
→ technisch einfach, redaktionell nicht ideal

Da du ein redakteurstaugliches System willst, ist A richtig.

Ich gehe deshalb unten von MultiColumnWizard aus.
9) src/Resources/contao/dca/tl_content.php

<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['tabs']
    = '{type_legend},type,headline;'
    . '{tabs_legend},tabsStyle,tabsLayout,tabsItems;'
    . '{protected_legend:hide},protected;'
    . '{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsStyle'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsStyle'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['default', 'pills', 'underline'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'default'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsLayout'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsLayout'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['horizontal', 'stack-mobile'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'horizontal'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsItems'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItems'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'tl_class' => 'clr',
        'columnFields' => [
            'label' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'],
                'inputType' => 'text',
                'eval'      => [
                    'style'     => 'width:220px',
                    'mandatory' => true,
                ],
            ],
            'content' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'],
                'inputType' => 'textarea',
                'eval'      => [
                    'style'     => 'width:420px;height:120px',
                    'allowHtml' => true,
                    'rte'       => 'tinyMCE',
                    'mandatory' => true,
                ],
            ],
        ],
    ],
    'sql'       => "blob NULL",
];

10) src/ContentElement/TabsElement.php

<?php

namespace Vendor\TabsBundle\ContentElement;

use Contao\ContentElement;
use Contao\StringUtil;

class TabsElement extends ContentElement
{
    protected $strTemplate = 'tabs';

    protected function compile(): void
    {
        $this->Template->tabsStyle  = (string) $this->tabsStyle;
        $this->Template->tabsLayout = (string) $this->tabsLayout;

        $items = StringUtil::deserialize($this->tabsItems, true);
        $prepared = [];

        foreach ($items as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $content = (string) ($item['content'] ?? '');

            if ($label === '' && trim($content) === '') {
                continue;
            }

            $prepared[] = [
                'index'   => $index,
                'id'      => 'tab-' . $this->id . '-' . $index,
                'panelId' => 'panel-' . $this->id . '-' . $index,
                'label'   => $label,
                'content' => StringUtil::decodeEntities($content),
                'active'  => \count($prepared) === 0,
            ];
        }

        $this->Template->items = $prepared;
        $this->Template->hasItems = !empty($prepared);
    }
}

11) src/Resources/contao/templates/tabs.html5

<?php if (!$this->hasItems): ?>
    <!-- no tabs -->
<?php else: ?>

<div class="tabs tabs--<?= $this->tabsStyle ?> layout--<?= $this->tabsLayout ?>" data-tabs>
    <div class="tabs__nav" role="tablist">
        <?php foreach ($this->items as $item): ?>
            <button
                class="tabs__button<?= $item['active'] ? ' is-active' : '' ?>"
                id="<?= $item['id'] ?>"
                type="button"
                role="tab"
                aria-selected="<?= $item['active'] ? 'true' : 'false' ?>"
                aria-controls="<?= $item['panelId'] ?>"
                data-tab-target="#<?= $item['panelId'] ?>"
            >
                <?= $item['label'] ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="tabs__panels">
        <?php foreach ($this->items as $item): ?>
            <div
                class="tabs__panel<?= $item['active'] ? ' is-active' : '' ?>"
                id="<?= $item['panelId'] ?>"
                role="tabpanel"
                aria-labelledby="<?= $item['id'] ?>"
                <?= $item['active'] ? '' : 'hidden' ?>
            >
                <?= $item['content'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

12) src/Resources/contao/languages/de/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['tabs'] = ['Tabs', 'Tab-Inhalte mit frei pflegbaren Panels.'];

$GLOBALS['TL_LANG']['tl_content']['tabs_legend'] = 'Tabs';

$GLOBALS['TL_LANG']['tl_content']['tabsStyle'] = ['Style', 'Darstellungsstil der Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsLayout'] = ['Layout', 'Layout der Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItems'] = ['Tab-Elemente', 'Tabs und ihre Inhalte.'];

$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'] = ['Label', 'Beschriftung des Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'] = ['Inhalt', 'Inhalt des Panels.'];

13) src/Resources/contao/languages/en/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['tabs'] = ['Tabs', 'Tab content with editable panels.'];

$GLOBALS['TL_LANG']['tl_content']['tabs_legend'] = 'Tabs';

$GLOBALS['TL_LANG']['tl_content']['tabsStyle'] = ['Style', 'Visual style of the tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsLayout'] = ['Layout', 'Layout mode of the tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItems'] = ['Tab items', 'Tabs and their panel content.'];

$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'] = ['Label', 'Tab label.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'] = ['Content', 'Panel content.'];

14) CSS-Hooks

.tabs {}
.tabs__nav {}
.tabs__button {}
.tabs__button.is-active {}
.tabs__panels {}
.tabs__panel {}
.tabs__panel.is-active {}

.tabs--default {}
.tabs--pills {}
.tabs--underline {}

.layout--horizontal {}
.layout--stack-mobile {}

15) Minimale JS-Hooks

Das Bundle selbst muss noch kein JS mitbringen, aber die erwartete Struktur ist jetzt klar:

    Wrapper: [data-tabs]

    Button: [data-tab-target]

    aktive Klassen: .is-active

Damit kannst du projektseitig extrem einfach ein kleines Tabs-JS bauen.
16) Wichtiger Hinweis zu multiColumnWizard

Wenn ihr das nicht ohnehin standardisiert im Projekt habt, musst du entscheiden:

    entweder multiColumnWizard als Abhängigkeit

    oder V1 ohne Repeater anders lösen

Mein Rat:
Wenn euer System langfristig Tabs, Accordion, evtl. Timeline/Facts mit Repeatern haben soll, dann standardisiert jetzt eine Repeater-Lösung. Sonst baust du jedes zweite CE doppelt.
17) Fazit

Damit hast du jetzt im exakt selben Stil:

    layout-preset

    tabs

Als nächstes wäre logisch:
accordion

im selben Muster.

Wenn du willst, mache ich jetzt direkt accordion genauso.

	•	entweder multiColumnWizard als Abhängigkeit
Hat das Nachteile?

Ja. Es hat Nachteile. Aber nicht automatisch schlimme.

Die Frage ist nicht „schlecht oder gut“, sondern:

    Willst du eine zusätzliche Abhängigkeit in deinem Grundsystem akzeptieren, um Repeater sauber im Backend zu bekommen?

Vorteile von multiColumnWizard

    Für Redakteure deutlich angenehmer als JSON/Textareas

    Perfekt für:

        Tabs

        Accordion

        Timeline

        Facts

        Stats

    Spart dir viel eigene Widget-/DCA-Arbeit

    In Contao-Projekten bewährt

Nachteile
1) Zusätzliche Abhängigkeit

Du bist dann nicht mehr nur auf Core + deine Bundles angewiesen.

Das heißt:

    Composer-Abhängigkeit mehr

    Kompatibilität bei Updates prüfen

    Wenn das Paket mal nicht gepflegt wird, hängt dein ganzes System dran

2) Backend-UX ist nicht immer „modern schön“

Es funktioniert, aber MultiColumnWizard ist eher:

    praktisch

    nicht super elegant

    bei vielen Einträgen schnell unübersichtlich

3) Tiefer verschachtelte Inhalte werden hässlich

Für einfache Repeater gut. Für:

    Repeater im Repeater

    komplexe Inhalte pro Item
    wird es schnell unerquicklich.

Für Tabs/Accordion reicht es aber.
4) Portabilität deines Starter-Systems

Wenn du dein GitHub-System als „leicht installierbar“ willst, musst du dann immer mitdenken:

    diese Abhängigkeit muss mit rein

    diese Abhängigkeit muss auch unter 4.13 / PHP 8 sauber laufen

Meine Einschätzung für deinen Fall

Für Tabs / Accordion / Timeline / Facts ist multiColumnWizard inhaltlich passend.

Aber:
Du baust gerade einen langfristigen Grundstamm.
Da ist jede externe Abhängigkeit ein Multiplikator.
Deshalb meine klare Empfehlung
Für V1:

Keine Abhängigkeit auf multiColumnWizard im Kernsystem.

Stattdessen:

    Tabs erstmal einfacher bauen

    Accordion auch

    notfalls zunächst mit begrenzter Feldanzahl

    Repeater erst dann reinziehen, wenn wirklich nötig

Warum?
Weil du im Moment gerade:

    layout_preset

    tabs

    accordion

    evtl. später chart

aufbauen willst.

Wenn du jetzt schon einen Repeater-Stack einziehst, wächst die Komplexität sofort.
Konkret für Tabs

Du hast zwei Wege:
A) V1 ohne MultiColumnWizard

z. B. erstmal:

    Tab 1 Label + Content

    Tab 2 Label + Content

    Tab 3 Label + Content

    optional Tab 4

Nicht elegant, aber:

    null Zusatzabhängigkeit

    extrem robust

    sofort baubar

B) V2 mit Repeater

Wenn du siehst:

    das CE funktioniert

    das System lebt

    du brauchst mehr Flexibilität

dann kann man auf Repeater umstellen.
Mein Rat in einem Satz

    Wenn dein Ziel gerade Stabilität + Systemaufbau ist: erst ohne multiColumnWizard.

Später kannst du immer noch upgraden.

Wenn du willst, kann ich dir jetzt direkt sagen, wie ich Tabs V1 ohne MultiColumnWizard strukturieren würde, damit es:

    sauber

    redaktionell verständlich

    und trotzdem brauchbar ist.


Was heißt Repeater in dem fall - und was genau leistet das Modul multicolumn wizard

Kurz und klar:
Was ist ein „Repeater“ in deinem Fall?

Ein Repeater bedeutet:

    Du kannst beliebig viele gleichartige Einträge hinzufügen.

Beispiel Tabs

Ohne Repeater:

    Tab 1 (fix)

    Tab 2 (fix)

    Tab 3 (fix)

Mit Repeater:

    „+ Tab hinzufügen“

    beliebig viele Tabs

    jeder Tab hat:

        Label

        Inhalt

Beispiel Datenstruktur (gedanklich)
Ohne Repeater

tab1_label = "Vision"
tab1_content = "..."
tab2_label = "Process"
tab2_content = "..."

Mit Repeater

[
  { "label": "Vision", "content": "..." },
  { "label": "Process", "content": "..." },
  { "label": "Mission", "content": "..." }
]

Das ist der Unterschied.
Was macht multiColumnWizard konkret?

Das ist ein Backend-Widget für Contao, das genau sowas ermöglicht.
Im Backend sieht das so aus:

Eine Tabelle mit Spalten:
Label	Content
Vision	Text...
Process	Text...
Mission	Text...

Und du kannst:

    Zeilen hinzufügen

    Zeilen löschen

    Reihenfolge ändern

Technisch macht das Modul:

    speichert alle Einträge in einem Feld (meist blob)

    serialisiert die Daten

    gibt dir im PHP ein Array zurück

Beispiel Rückgabe im PHP

[
  [
    'label' => 'Vision',
    'content' => '...'
  ],
  [
    'label' => 'Process',
    'content' => '...'
  ]
]

Damit kannst du dann im Template arbeiten.
Warum man das überhaupt braucht

Ohne sowas hast du drei Optionen:
1) Feste Felder (schlecht skalierbar)

    Tab1, Tab2, Tab3

    unflexibel

    irgendwann reicht es nicht

2) JSON/Textarea (unbenutzbar für Redakteure)

    technisch ok

    praktisch unbrauchbar

3) Eigene Child-Table (Overkill für dich)

    sauberste Lösung

    aber viel mehr Aufwand

Wo du Repeater brauchst

In deinem System:
Sinnvoll für:

    Tabs

    Accordion

    Timeline

    Facts

    Stats

Nicht nötig für:

    layout_preset

    einfache Text-/Split-Strukturen

Der eigentliche Tradeoff
Mit multiColumnWizard

    flexibel

    redakteursfreundlich

    schnell umsetzbar
    − zusätzliche Abhängigkeit

Ohne

    keine Abhängigkeit
    − unflexibel oder unsauber

Klare Einschätzung für dein Projekt

Du willst:

    wiederverwendbare CEs

    redakteurstauglich

    langfristig nutzbar

Dann ist die ehrliche Antwort:

    Ohne Repeater wirst du bei Tabs/Accordion schnell an Grenzen stoßen.

Empfehlung (nicht weichgespült)
Für dein System:

    Ja, Repeater brauchst du

    Ja, multiColumnWizard ist der pragmatischste Weg

Aber:

    Nicht blind überall einsetzen – nur für echte Listen-Strukturen

Wenn du strategisch sauber bleiben willst

Mach es so:
Phase 1

    Tabs + Accordion mit multiColumnWizard

Phase 2 (optional später)

    eigene Repeater-Logik / eigenes Widget

    wenn du komplett unabhängig sein willst

Kurzfassung

    Repeater = „beliebig viele gleichartige Einträge“

    multiColumnWizard = „Backend-Tabelle für genau das“

    Für Tabs/Accordion praktisch Standardlösung in Contao

Wenn du willst, zeige ich dir als Nächstes:
„Tabs V1 MIT multiColumnWizard – aber sauber und nicht overengineered“

→ also genau so, dass es zu deinem System passt und nicht ausartet.

Nein Multi column wizard Abhängigkeit ist völlig okay - wird sowieso oft gebraucht

Perfekt. Dann ist die Entscheidung klar:
Repeater-Strategie steht

    multiColumnWizard ist gesetzt

    Das ist für dein System sinnvoll

    Und es spart dir auf Dauer deutlich mehr Zeit, als es kostet

Damit gilt für die nächsten CEs:

    tabs → mit Repeater

    accordion → mit Repeater

    später optional:

        timeline

        facts

        stats

Das ist sauber, konsistent und redakteurstauglich.
Konsequenz für dein System
Was multiColumnWizard jetzt für euch leistet

Du kannst in den CEs echte Inhaltslisten abbilden, statt:

    harte Felder tab1, tab2, tab3

    JSON-Gefrickel

    eigene Child-Tables

    unlesbare DCA-Konstruktionen

Das heißt:

    Eure CE-Bausteine werden jetzt wirklich langfristig brauchbar.

Was das konkret für tabs bedeutet

tabs kann jetzt sauber so gebaut werden:
Repeater-Spalten

    label

    content

Optional später:

    icon

    anchor

    active

Für V1 reichen:

    label

    content

Mehr nicht.
Was das konkret für accordion bedeutet

accordion kann dann genauso sauber werden:
Repeater-Spalten

    title

    content

Optional später:

    icon

    openByDefault

Auch hier:
V1 nur Titel + Inhalt.
Strategische Folge

Damit steht eure Architektur jetzt endgültig so:
Bestehend

    article_insert

    members_grid

Neu

    layout_preset

    tabs (mit multiColumnWizard)

    accordion (mit multiColumnWizard)

Optional später

    chart

    evtl. stats

    evtl. timeline

Nächster sinnvoller Schritt

Da multiColumnWizard jetzt akzeptiert ist, ist die logisch richtige Reihenfolge:
1. layout_preset fertig auf GitHub anlegen
2. tabs mit multiColumnWizard
3. accordion im selben Muster
Wichtig

Dann solltest du in deinen neuen Bundles direkt im composer.json die Abhängigkeit mit aufnehmen.

Je nachdem, welches Paket du nutzt, kommt dort dann z. B. etwas wie:

"require": {
  "php": "^8.0",
  "contao/core-bundle": "^4.13",
  "contao/manager-plugin": "^2.0",
  ".../multi-column-wizard-bundle": "^..."
}

Den exakten Paketnamen müssen wir dann sauber festlegen – je nachdem, welches MCW-Paket du in deinen Projekten standardmäßig nutzen willst.

Wenn du willst, machen wir als Nächstes direkt sauber weiter mit:
Tabs-Bundle final in der Version mit MultiColumnWizard

also:

    korrigiertes composer.json mit Abhängigkeit

    DCA

    Bundle-Struktur

    Template

    Klasse

Dann ist das erste echte Repeater-CE sauber definiert.

Tabs-Bundle final in der Version mit MultiColumnWizard

Ja. Dann korrigieren wir tabs sauber auf die MCW-Version.

Der gepflegte Paketname ist menatwork/contao-multicolumnwizard-bundle; das alte Paket menatwork/contao-multicolumnwizard ist als veraltet markiert. Auf Packagist und im Contao-Store wird für das Bundle die Linie 3.4.x / 3.5.x geführt; in Contao-4.13-Projekten taucht ^3.5 auch in realen Composer-Konfigurationen auf.
Was du am tabs-Bundle ändern solltest
1) composer.json

Nimm das hier:

{
  "name": "vtxm-h/tabs",
  "description": "Contao 4.13 content element: tabs with repeatable items.",
  "type": "contao-bundle",
  "license": "MIT",
  "require": {
    "php": "^8.0",
    "contao/core-bundle": "^4.13",
    "contao/manager-plugin": "^2.0",
    "menatwork/contao-multicolumnwizard-bundle": "^3.5"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\TabsBundle\\": "src/"
    }
  },
  "extra": {
    "contao-manager-plugin": "Vendor\\TabsBundle\\ContaoManager\\Plugin"
  }
}

2) DCA-Feld tabsItems

Dein DCA mit

'inputType' => 'multiColumnWizard',

ist dann richtig. Das kannst du so lassen.
Was ich dir noch klar sagen würde

Für dein System ist MCW als Standardabhängigkeit vertretbar, weil du damit nicht nur Tabs, sondern später auch:

    Accordion

    Timeline

    Facts

    Stats

sauber und konsistent abbilden kannst. Das ist genau der Punkt, an dem eine externe Abhängigkeit mehr Nutzen als Schaden bringt.
Praktischer Hinweis

Wenn du das Repo jetzt erst anlegst, würde ich die Reihenfolge so machen:

    tabs Repo mit Grundstruktur

    composer.json direkt mit MCW-Abhängigkeit

    erst dann DCA/Klasse/Template committen

So siehst du sofort, ob die Paketauflösung im Zielprojekt sauber läuft.

Wenn du willst, gebe ich dir jetzt direkt die korrigierte finale tabs-Version noch einmal komplett aus, diesmal mit der MCW-Abhängigkeit bereits sauber integriert.

Ja gerne

Gut. Dann hier die finale tabs-Erweiterung V1 mit MultiColumnWizard, konsistent zu deinem Schema:

    Struktur wie members-grid

    Template-Name frei: tabs.html5

    Namespace-Stil wie bei euren Bundles

    Contao 4.13

    MCW direkt als Abhängigkeit

1) Repo / Paket / Namespace
Repo

tabs

Composer-Paket

"vtxm-h/tabs"

Namespace

Vendor\TabsBundle

CE-Typ

tabs

Template

tabs.html5

2) Ordnerstruktur

tabs/
├── composer.json
└── src/
    ├── TabsBundle.php
    ├── ContaoManager/
    │   └── Plugin.php
    ├── ContentElement/
    │   └── TabsElement.php
    └── Resources/
        ├── config/
        │   └── services.yaml
        └── contao/
            ├── config/
            │   └── config.php
            ├── dca/
            │   └── tl_content.php
            ├── languages/
            │   ├── de/
            │   │   └── tl_content.php
            │   └── en/
            │       └── tl_content.php
            └── templates/
                └── tabs.html5

3) composer.json

{
  "name": "vtxm-h/tabs",
  "description": "Contao 4.13 content element: tabs with repeatable items.",
  "type": "contao-bundle",
  "license": "MIT",
  "require": {
    "php": "^8.0",
    "contao/core-bundle": "^4.13",
    "contao/manager-plugin": "^2.0",
    "menatwork/contao-multicolumnwizard-bundle": "^3.5"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\TabsBundle\\": "src/"
    }
  },
  "extra": {
    "contao-manager-plugin": "Vendor\\TabsBundle\\ContaoManager\\Plugin"
  }
}

4) src/TabsBundle.php

<?php

namespace Vendor\TabsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TabsBundle extends Bundle
{
}

5) src/ContaoManager/Plugin.php

<?php

namespace Vendor\TabsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Vendor\TabsBundle\TabsBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(TabsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}

6) src/Resources/contao/config/config.php

<?php

$GLOBALS['TL_CTE']['vtxm']['tabs']
    = \Vendor\TabsBundle\ContentElement\TabsElement::class;

7) src/Resources/config/services.yaml

services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Vendor\TabsBundle\:
    resource: '../../*'
    exclude: '../../{Resources,ContaoManager,DependencyInjection,Entity,Tests}'

8) src/Resources/contao/dca/tl_content.php

<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['tabs']
    = '{type_legend},type,headline;'
    . '{tabs_legend},tabsStyle,tabsLayout,tabsItems;'
    . '{protected_legend:hide},protected;'
    . '{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsStyle'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsStyle'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['default', 'pills', 'underline'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'default'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsLayout'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsLayout'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['horizontal', 'stack-mobile'],
    'eval'      => [
        'mandatory' => true,
        'chosen'    => true,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(32) NOT NULL default 'horizontal'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['tabsItems'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItems'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'tl_class' => 'clr',
        'columnFields' => [
            'label' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'],
                'inputType' => 'text',
                'eval'      => [
                    'style'     => 'width:220px',
                    'mandatory' => true,
                ],
            ],
            'content' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'],
                'inputType' => 'textarea',
                'eval'      => [
                    'style'     => 'width:420px;height:140px',
                    'allowHtml' => true,
                    'rte'       => 'tinyMCE',
                    'mandatory' => true,
                ],
            ],
        ],
    ],
    'sql'       => "blob NULL",
];

9) src/ContentElement/TabsElement.php

<?php

namespace Vendor\TabsBundle\ContentElement;

use Contao\ContentElement;
use Contao\StringUtil;

class TabsElement extends ContentElement
{
    protected $strTemplate = 'tabs';

    protected function compile(): void
    {
        $this->Template->tabsStyle  = (string) $this->tabsStyle;
        $this->Template->tabsLayout = (string) $this->tabsLayout;

        $items = StringUtil::deserialize($this->tabsItems, true);
        $prepared = [];

        foreach ($items as $index => $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $content = (string) ($item['content'] ?? '');

            if ($label === '' && trim($content) === '') {
                continue;
            }

            $prepared[] = [
                'index'   => $index,
                'id'      => 'tab-' . $this->id . '-' . $index,
                'panelId' => 'panel-' . $this->id . '-' . $index,
                'label'   => $label,
                'content' => StringUtil::decodeEntities($content),
                'active'  => \count($prepared) === 0,
            ];
        }

        $this->Template->items = $prepared;
        $this->Template->hasItems = !empty($prepared);
    }
}

10) src/Resources/contao/templates/tabs.html5

<?php if (!$this->hasItems): ?>
    <!-- no tabs -->
<?php else: ?>

<div class="tabs tabs--<?= $this->tabsStyle ?> layout--<?= $this->tabsLayout ?>" data-tabs>
    <div class="tabs__nav" role="tablist">
        <?php foreach ($this->items as $item): ?>
            <button
                class="tabs__button<?= $item['active'] ? ' is-active' : '' ?>"
                id="<?= $item['id'] ?>"
                type="button"
                role="tab"
                aria-selected="<?= $item['active'] ? 'true' : 'false' ?>"
                aria-controls="<?= $item['panelId'] ?>"
                data-tab-target="#<?= $item['panelId'] ?>"
            >
                <?= $item['label'] ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="tabs__panels">
        <?php foreach ($this->items as $item): ?>
            <div
                class="tabs__panel<?= $item['active'] ? ' is-active' : '' ?>"
                id="<?= $item['panelId'] ?>"
                role="tabpanel"
                aria-labelledby="<?= $item['id'] ?>"
                <?= $item['active'] ? '' : 'hidden' ?>
            >
                <?= $item['content'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>

11) src/Resources/contao/languages/de/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['tabs'] = ['Tabs', 'Tab-Inhalte mit frei pflegbaren Panels.'];

$GLOBALS['TL_LANG']['tl_content']['tabs_legend'] = 'Tabs';

$GLOBALS['TL_LANG']['tl_content']['tabsStyle'] = ['Style', 'Darstellungsstil der Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsLayout'] = ['Layout', 'Layout der Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItems'] = ['Tab-Elemente', 'Tabs und ihre Inhalte.'];

$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'] = ['Label', 'Beschriftung des Tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'] = ['Inhalt', 'Inhalt des Panels.'];

12) src/Resources/contao/languages/en/tl_content.php

<?php

$GLOBALS['TL_LANG']['CTE']['vtxm'] = 'VTXM';
$GLOBALS['TL_LANG']['CTE']['tabs'] = ['Tabs', 'Tab content with editable panels.'];

$GLOBALS['TL_LANG']['tl_content']['tabs_legend'] = 'Tabs';

$GLOBALS['TL_LANG']['tl_content']['tabsStyle'] = ['Style', 'Visual style of the tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsLayout'] = ['Layout', 'Layout mode of the tabs.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItems'] = ['Tab items', 'Tabs and their panel content.'];

$GLOBALS['TL_LANG']['tl_content']['tabsItemLabel'] = ['Label', 'Tab label.'];
$GLOBALS['TL_LANG']['tl_content']['tabsItemContent'] = ['Content', 'Panel content.'];

13) CSS-Hooks

.tabs {}
.tabs__nav {}
.tabs__button {}
.tabs__button.is-active {}
.tabs__panels {}
.tabs__panel {}
.tabs__panel.is-active {}

.tabs--default {}
.tabs--pills {}
.tabs--underline {}

.layout--horizontal {}
.layout--stack-mobile {}

14) Minimales JS für Tabs

Das gehört nicht zwingend ins Bundle, aber so sollte die Struktur aussehen:

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
    const buttons = tabs.querySelectorAll('.tabs__button');
    const panels = tabs.querySelectorAll('.tabs__panel');

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        const target = tabs.querySelector(button.getAttribute('data-tab-target'));

        buttons.forEach(function (btn) {
          btn.classList.remove('is-active');
          btn.setAttribute('aria-selected', 'false');
        });

        panels.forEach(function (panel) {
          panel.classList.remove('is-active');
          panel.setAttribute('hidden', 'hidden');
        });

        button.classList.add('is-active');
        button.setAttribute('aria-selected', 'true');

        if (target) {
          target.classList.add('is-active');
          target.removeAttribute('hidden');
        }
      });
    });
  });
});

15) Wichtig für Installation

Im Projekt dann einfach:

"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/vtxm-h/tabs.git"
  }
]

und danach:

composer require vtxm-h/tabs

16) Mein Rat für den nächsten Schritt

Jetzt nicht sofort Accordion mitdenken, sondern:

    Repo anlegen

    Dateien reinkippen

    Installieren

    Prüfen:

        CE sichtbar?

        DB-Update?

        Backend-Widget mit MCW funktioniert?

        erstes Rendering im Frontend?

Danach gehen wir an accordion.

Wenn du willst, mache ich dir direkt danach noch eine ultraknappe GitHub-README-Installationssektion im selben Stil wie vorhin.

Repo anlegen
Dateien reinkippen
Installieren
Prüfen:

das müsste ich erstmal mit layout grid machen...

Ja. Genau. Erst layout-preset sauber fertig machen und testen.

Das ist auch die richtige Reihenfolge, weil:

    layout_preset ist der Orchestrator

    tabs und später accordion hängen logisch daran

    wenn das Slot-Prinzip sauber läuft, weißt du sofort, wie die anderen CEs eingebettet werden

Also jetzt sinnvoll:

    Repo layout-preset anlegen

    Grunddateien rein

    installieren

    prüfen:

        Bundle wird geladen

        CE erscheint im Backend

        DB-Update sauber

        article/module/html Slots funktionieren

Erst danach:

    tabs

    accordion

Das spart dir viel Chaos.
Kleiner wichtiger Hinweis

Du hast eben „layout grid“ geschrieben, aber wir meinen weiterhin layout-preset, richtig?
Nicht, dass wir jetzt unbemerkt den Namen wechseln.

Wenn du beim Anlegen des Repos einen Moment unsicher bist, halte dich an:

    Repo: layout-preset

    Paket: vtxm-h/layout-preset

    Namespace: Vendor\LayoutPresetBundle

Sobald du das Repo angelegt hast, gehen wir die ersten Dateien nacheinander durch.

wie war denn das mit members grid...

Gut, dass du das nochmal aufziehst — daran solltest du dich 1:1 orientieren.
members-grid ist dein Referenz-Blueprint.

Ich breche dir das runter auf das, was du jetzt konkret brauchst, nicht Theorie.
Wie lief members-grid wirklich ab?
1. Repo anlegen

members-grid

2. Minimales Set an Dateien

Am Anfang hattest du noch kein DCA, kein Template, keine Logik, sondern nur:
Pflicht für Contao:

    composer.json

    Bundle.php

    Plugin.php

→ damit das Bundle überhaupt geladen wird
3. Dann Installation testen

composer require vtxm-h/members-grid

Check:

    taucht im Contao Manager auf

    kein Fehler beim Install

    vendor/ enthält Bundle

4. Dann erst Content Element registrieren

Datei:

Resources/contao/config/config.php

$GLOBALS['TL_CTE']['vtxm']['members_grid']
    = Vendor\MembersGridBundle\ContentElement\MembersGridElement::class;

Check:

    CE taucht im Backend auf (auch ohne DCA!)

5. Dann DCA hinzufügen

Datei:

Resources/contao/dca/tl_content.php

→ Felder definieren
Check:

    Backend lädt ohne Fehler

    Felder sichtbar

6. Dann Template

Datei:

templates/members_grid.html5

→ erstmal minimal:

<div>members grid</div>

Check:

    FE rendert

    keine Exceptions

7. Dann Logik

Klasse:

MembersGridElement.php

→ compile() befüllt Template
Was du jetzt für layout-preset tun solltest

Mach exakt denselben Ablauf.
Phase 1 (wichtig)

Nur diese Dateien:
1. composer.json
2. LayoutPresetBundle.php
3. Plugin.php

→ commit
Phase 2
4. config.php (CE registrieren)

→ prüfen:

    CE sichtbar

Phase 3
5. tl_content.php (DCA)

→ prüfen:

    Backend lädt sauber

Phase 4
6. Template minimal

<div>layout preset</div>

→ prüfen:

    Frontend zeigt es

Phase 5
7. CE-Klasse Logik
Wichtigster Unterschied zu members-grid

Bei members-grid:
→ Daten kommen aus einer Quelle (Members)

Bei layout-preset:
→ du hast 3 Quellen:

    article

    module

    html

Das ist die einzige echte Komplexität.
Typischer Fehler (den du vermeiden solltest)

Nicht sofort alles reinballern:

    DCA komplett

    Template fertig

    Logik fertig

    Article loading

→ dann suchst du 3 Stunden einen kleinen Fehler
Richtige Strategie (wie bei members-grid)

    Immer nur eine Ebene hinzufügen und sofort testen

Kurz gesagt

Mach jetzt:

    Repo layout-preset

    nur Bundle + Plugin

    installieren

    dann Schritt für Schritt erweitern

Wenn du willst, begleite ich dich jetzt live durch:
Schritt 1: dein konkretes composer.json prüfen

→ bevor du commitest
→ damit du dir keinen strukturellen Fehler reinziehst wie damals

ja hab aber schon alles fertig

Gut. Dann nicht zurückrudern.

Der richtige Schritt ist jetzt:
1) Repo so lassen wie es ist

Wenn du layout-preset schon komplett angelegt hast, dann jetzt nicht wieder auf Minimalversion zurückbauen.
2) Jetzt nur noch sauber testen – in genau dieser Reihenfolge
A. GitHub / Struktur

Prüfen:

    composer.json

    src/LayoutPresetBundle.php

    src/ContaoManager/Plugin.php

    src/Resources/...

Wenn das formal steht, ist gut.
B. Composer-Install im Projekt

Im Projekt-composer.json:

"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/vtxm-h/layout-preset"
  }
]

Dann:

composer require vtxm-h/layout-preset

C. Danach prüfen

    Wird das Bundle installiert?

    Taucht das CE layout_preset im Backend auf?

    Gibt es ein DB-Update?

    Lässt sich ein CE vom Typ layout_preset anlegen?

    Lädt das Backend ohne Fehler?

D. Erst dann Frontend

Test-CE anlegen:

    Preset: default

    Mode: left-right

    Slot A: html

    Slot B: html

Einfacher Testinhalt links/rechts.

Wenn das im FE sauber kommt:
→ Grundsystem steht
3) Erst danach die kritischen Fälle testen
Test 1: html | html

    einfachster Fall

Test 2: module | html

    z. B. rechts Formularmodul

Test 3: article | html

    Artikel aus Hidden Content Page

Test 4: article | article

    eigentlicher Hauptfall

Wenn article | article sauber geht, ist das Ding praktisch durch.
4) Worauf du am ehesten achten musst

Bei layout-preset sind die wahrscheinlichen Fehlerquellen:

    Namespace / Plugin ok, aber CE erscheint nicht

    DCA ok, aber options_callback für Artikel greift nicht

    Modul-Rendering funktioniert, Artikel-Rendering nicht

    Template lädt, aber Reihenfolge left-right / right-left ist falsch

    slotAPage/slotBPage werden nicht sauber ausgewertet

Das sind die Stellen, wo du jetzt hinschauen musst.
5) Mein Vorschlag

Mach jetzt genau einen Testlauf und gib mir dann einen dieser Zustände zurück:

    install läuft

    CE erscheint nicht

    Backendfehler

    Frontend rendert leer

    article-slot geht nicht

    module-slot geht nicht

Dann steigen wir exakt an der richtigen Stelle ein.

readme wie hier für layout preset und tabs bitte

Hier sind zwei saubere, konsistente READMEs im exakt gleichen Stil wie dein members-grid.
README – layout-preset

# Layout Preset (Contao)

Reusable Contao content element that provides a flexible layout wrapper with two slots (A/B).
Each slot can render an article, a frontend module, or custom HTML.

Designed to be theme-agnostic: the bundle outputs clean HTML hooks, styling is handled in your project CSS.

---

## Features

- Content element: **Layout preset**
- 2 flexible slots (A / B)
- Slot types:
  - Article (via Contao articles)
  - Module (frontend modules)
  - HTML (free content)
- Layout modes:
  - left-right
  - right-left
  - top-bottom
  - bottom-top
- Presets:
  - about
  - contact
  - spotlight
  - default
- Optional:
  - divider
  - mobile stacking

---

## Template

layout_preset.html5

---

## Installation (via Composer / Contao Manager)

Add the repository to your Contao project `composer.json` (root of the Contao installation):

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/vtxm-h/layout-preset"
    }
  ]
}

Installation:

composer require vtxm-h/layout-preset

Usage

    Add a new content element of type Layout preset

    Choose:

        preset (about / contact / spotlight / default)

        layout mode (left-right, etc.)

    Configure Slot A and Slot B:

        Article → select page + article

        Module → select frontend module

        HTML → enter custom content



## Compatibility

Contao 4.13
PHP 8.0+



## License

MIT
