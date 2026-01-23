# Members Grid Bundle (Contao 4.13)

Reusable Contao **content element** that renders a 4-person **members cutout grid** (left / top / right / bottom).  
Designed to be **theme-agnostic**: the bundle outputs clean HTML hooks, styling is handled in your project CSS.

---

## Features

- Content element: **Members grid**
- 4 slots:
  - Left
  - Top (center)
  - Right
  - Bottom (center)
- Each slot:
  - Image (UUID via fileTree)
  - Caption/name (text)
- Neutral markup and class names (no project-specific naming)
- Works with **Contao 4.13**

---

## Requirements

- PHP: `^7.4 || ^8.0 || ^8.1`
- Contao: `^4.13`

---

## Installation (via Composer / Contao Manager)

Add the repository to your **Contao project** `composer.json` (root of the Contao installation):

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/vtxm-h/members_grid"
    }
  ]
}
