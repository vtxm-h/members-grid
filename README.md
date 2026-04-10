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

Requirements

This bundle requires:

menatwork/contao-multicolumnwizard-bundle

Installed automatically via Composer.

Usage
Add a new content element of type Tabs
Define tab items:
Label
Content
Choose style and layout
Add frontend JS for interaction (not included by design)
Notes
The bundle does not include JavaScript behavior intentionally.
Use your own JS (e.g. vanilla, GSAP, etc.) to control tab switching.
HTML structure includes hooks for easy scripting:
[data-tabs]
.tabs__button
.tabs__panel
