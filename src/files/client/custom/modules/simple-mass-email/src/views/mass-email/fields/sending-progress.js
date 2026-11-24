/************************************************************************
 * This file is part of Simple Mass Email for EspoCRM.
 *
 * Copyright (C) 2026 Alexis SANTIN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 ************************************************************************/

/**
 * Progress of a mass email sent in batches through a personal account.
 */

import BaseFieldView from 'views/fields/base';

class SimpleMassEmailSendingProgressFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#each lines}}<div>{{this}}</div>{{/each}}
    `;

    data() {
        return {...super.data(), lines: this.getLines()};
    }

    getLines() {
        const progress = this.model.get(this.name);

        if (!progress) {
            return [];
        }

        const lines = [this.format('progressCounts', progress)];

        // A personal account: its limit over 24 hours.
        if (!progress.limit) {
            return lines;
        }

        lines.push(this.format('progressWindow', progress));

        if (this.model.get('status') !== 'Throttled' || !progress.pending) {
            return lines;
        }

        if (progress.nextSlotAt) {
            lines.push(this.format('progressNext', {
                date: this.getDateTime().toDisplay(progress.nextSlotAt),
            }));
        }

        lines.push(this.format('progressDays', progress));

        return lines;
    }

    format(key, values) {
        let text = this.translate(key, 'labels', 'SimpleMassEmail');

        Object.keys(values).forEach(name => {
            text = text.replace(`{${name}}`, values[name]);
        });

        return text;
    }
}

export default SimpleMassEmailSendingProgressFieldView;
