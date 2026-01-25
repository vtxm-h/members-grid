<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['members_grid']
    = '{type_legend},type,headline,hl;{members_legend},'
    . 'member_left_img,member_left_name,'
    . 'member_top_img,member_top_name,'
    . 'member_right_img,member_right_name,'
    . 'member_bottom_img,member_bottom_name;'
    . '{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$imgField = static function (string $name): array {
    return [
        'label'     => &$GLOBALS['TL_LANG']['tl_content'][$name],
        'exclude'   => true,
        'inputType' => 'fileTree',
        'eval'      => [
            'filesOnly'  => true,
            'fieldType'  => 'radio',
            'extensions' => 'png,webp,jpg,jpeg',
            'tl_class'   => 'clr w50',
        ],
        'sql'       => "binary(16) NULL",
    ];
};

$nameField = static function (string $name): array {
    return [
        'label'     => &$GLOBALS['TL_LANG']['tl_content'][$name],
        'exclude'   => true,
        'inputType' => 'text',
        'eval'      => [
            'maxlength' => 96,
            'tl_class'  => 'w50',
        ],
        'sql'       => "varchar(96) NOT NULL default ''",
    ];
};

foreach ([
    ['member_left_img','member_left_name'],
    ['member_top_img','member_top_name'],
    ['member_right_img','member_right_name'],
    ['member_bottom_img','member_bottom_name'],
] as [$img, $txt]) {
    $GLOBALS['TL_DCA']['tl_content']['fields'][$img] = $imgField($img);
    $GLOBALS['TL_DCA']['tl_content']['fields'][$txt] = $nameField($txt);
}
