// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Rubric editor.
 *
 * @module     mod_videorubric/rubric_editor
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/templates'], function(Templates) {
    'use strict';

    const reindexCriterion = (criterion, criterionIndex) => {
        criterion.dataset.index = criterionIndex;
        criterion.querySelectorAll('.videorubric-level').forEach((level, levelIndex) => {
            level.dataset.levelIndex = levelIndex;
            level.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name
                    .replace(/criteria\[\d+\]/, `criteria[${criterionIndex}]`)
                    .replace(/levels\[\d+\]/, `levels[${levelIndex}]`);
            });
        });
        criterion.querySelectorAll(':scope > .card-body > [name], :scope > .card-body > div [name]').forEach((field) => {
            field.name = field.name.replace(/criteria\[\d+\]/, `criteria[${criterionIndex}]`);
        });
    };

    const appendTemplate = async(container, template, context) => {
        const {html, js} = await Templates.renderForPromise(template, context);
        Templates.appendNodeContents(container, html, js);
        return container.lastElementChild;
    };

    const bindCriterion = (criterion) => {
        criterion.querySelector('.videorubric-remove-criterion')?.addEventListener('click', () => criterion.remove());

        criterion.querySelectorAll('.videorubric-remove-level').forEach((button) => {
            button.addEventListener('click', () => button.closest('.videorubric-level')?.remove());
        });

        criterion.querySelector('.videorubric-add-level')?.addEventListener('click', async() => {
            const criterionIndex = Number(criterion.dataset.index);
            const levels = criterion.querySelector('.videorubric-levels');
            if (!levels) {
                return;
            }
            const levelIndex = levels.querySelectorAll('.videorubric-level').length;
            const row = await appendTemplate(levels, 'mod_videorubric/rubric_level', {
                criterionindex: criterionIndex,
                index: levelIndex,
                id: 0,
                label: '',
                description: '',
                points: '0',
            });
            row?.querySelector('.videorubric-remove-level')?.addEventListener('click', () => row.remove());
        });
    };

    const init = () => {
        const container = document.getElementById('videorubric-criteria');
        const add = document.getElementById('videorubric-add-criterion');
        if (!container || !add) {
            return;
        }

        container.querySelectorAll('.videorubric-criterion').forEach(bindCriterion);

        add.addEventListener('click', async() => {
            const index = container.querySelectorAll('.videorubric-criterion').length;
            const criterion = await appendTemplate(container, 'mod_videorubric/rubric_criterion', {
                index: index,
                id: 0,
                name: '',
                description: '',
                weight: '1',
                levels: [
                    {criterionindex: index, index: 0, id: 0, label: '', description: '', points: '0'},
                    {criterionindex: index, index: 1, id: 0, label: '', description: '', points: '1'},
                ],
            });
            if (criterion) {
                bindCriterion(criterion);
            }
        });

        document.getElementById('videorubric-rubric-form')?.addEventListener('submit', () => {
            container.querySelectorAll('.videorubric-criterion').forEach((criterion, index) => {
                reindexCriterion(criterion, index);
            });
        });
    };

    return {init: init};
});
