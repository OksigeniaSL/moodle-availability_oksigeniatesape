# Restriction by Tesapé for Moodle (Oksigenia Tesapé)

[![License: GPLv3](https://img.shields.io/badge/license-GPLv3+-blue.svg)](LICENSE)
![Moodle](https://img.shields.io/badge/Moodle-4.5%E2%80%935.3-orange)
![Status](https://img.shields.io/badge/status-alpha-red)

An availability condition for Moodle that opens an activity or a section with the progress of
[Oksigenia Tesapé](https://github.com/OksigeniaSL/moodle-local_oksigeniatesape), the classroom role-playing game:

- **The class lights the lantern of a place** of the mission of the course (or passes it by).
- **The hero of the student reaches a level.**

It is added from «Restrict access» in the settings of an activity or a section, in courses where the game is set up.
Like any condition of Moodle, it can be negated and combined with others.

[Leer en español](README.es.md)

## Requirements

- Moodle 4.5 to 5.3; also tested on the development branch of Moodle 6.0.
- Oksigenia Tesapé (`local_oksigeniatesape`) 0.195.0 or later.

## Installation

Copy this repository to `availability/condition/oksigeniatesape` (from Moodle 5.0, `public/availability/condition/
oksigeniatesape`) and finish the installation from the notifications of the site.

## Privacy

It keeps no personal data: it reads the progress of the game.

## License

GNU GPL v3 or later. © 2026 Oksigenia SL.
