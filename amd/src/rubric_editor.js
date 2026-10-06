// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * rubric_editor.js
 *
 * @package   mod_videorubric
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    const reindexCriterion = (criterion, index) => {
        criterion.dataset.index = index;
        criterion.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace(/criteria\[\d+\]/, `criteria[${index}]`);
        });
    };

    const makeLevel = (criterionIndex, levelIndex) => {
        const row = document.createElement('div');
        row.className = 'videorubric-level border rounded p-3 mb-2 d-grid gap-2';
        row.dataset.levelIndex = levelIndex;
        row.innerHTML = `
            <input type="hidden" name="criteria[${criterionIndex}][levels][${levelIndex}][id]" value="0">
            <input class="form-control" name="criteria[${criterionIndex}][levels][${levelIndex}][label]" placeholder="Level" required>
            <textarea class="form-control" name="criteria[${criterionIndex}][levels][${levelIndex}][description]" rows="2" placeholder="Description"></textarea>
            <div class="d-flex gap-2">
                <input class="form-control" type="number" step="0.01" name="criteria[${criterionIndex}][levels][${levelIndex}][points]" value="0" placeholder="Points" required>
                <button type="button" class="btn btn-outline-secondary videorubric-remove-level">Remove</button>
            </div>`;
        return row;
    };

    const bindCriterion = (criterion) => {
        criterion.querySelector('.videorubric-remove-criterion')?.addEventListener('click', () => criterion.remove());
        criterion.querySelectorAll('.videorubric-remove-level').forEach((button) => {
            button.addEventListener('click', () => button.closest('.videorubric-level')?.remove());
        });
        criterion.querySelector('.videorubric-add-level')?.addEventListener('click', () => {
            const index = Number(criterion.dataset.index);
            const levels = criterion.querySelector('.videorubric-levels');
            const next = levels ? levels.querySelectorAll('.videorubric-level').length : 0;
            const row = makeLevel(index, next);
            levels?.append(row);
            row.querySelector('.videorubric-remove-level')?.addEventListener('click', () => row.remove());
        });
    };

    const init = () => {
        const container = document.getElementById('videorubric-criteria');
        const add = document.getElementById('videorubric-add-criterion');
        if (!container || !add) { return; }
        container.querySelectorAll('.videorubric-criterion').forEach(bindCriterion);
        add.addEventListener('click', () => {
            const index = container.querySelectorAll('.videorubric-criterion').length;
            const section = document.createElement('section');
            section.className = 'card mb-3 videorubric-criterion';
            section.dataset.index = index;
            section.innerHTML = `
                <div class="card-body">
                    <input type="hidden" name="criteria[${index}][id]" value="0">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <label class="form-label">Criterion</label>
                            <input class="form-control mb-2" name="criteria[${index}][name]" required>
                            <textarea class="form-control mb-2" name="criteria[${index}][description]" rows="2" placeholder="Description"></textarea>
                            <label class="form-label">Weight</label>
                            <input class="form-control videorubric-weight" type="number" min="0" step="0.01" name="criteria[${index}][weight]" value="1">
                        </div>
                        <button type="button" class="btn btn-outline-danger videorubric-remove-criterion">Remove</button>
                    </div>
                    <div class="videorubric-levels mt-3"></div>
                    <button type="button" class="btn btn-outline-primary videorubric-add-level">Add level</button>
                </div>`;
            container.append(section);
            const levels = section.querySelector('.videorubric-levels');
            [0, 1].forEach((levelIndex) => levels?.append(makeLevel(index, levelIndex)));
            bindCriterion(section);
        });

        document.getElementById('videorubric-rubric-form')?.addEventListener('submit', () => {
            container.querySelectorAll('.videorubric-criterion').forEach((criterion, index) => reindexCriterion(criterion, index));
        });
    };


    return {
        init: init,
    };
});
