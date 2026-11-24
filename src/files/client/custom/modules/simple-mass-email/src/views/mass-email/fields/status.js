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
 * Shows a mass email planned for later as "Scheduled", with its date.
 */

import EnumFieldView from 'views/fields/enum';
import {SENT_STATUSES, isFuture} from 'simple-mass-email:views/mass-email/helpers/sending';

class SimpleMassEmailStatusFieldView extends EnumFieldView {

    getAttributeList() {
        return [...super.getAttributeList(), 'startAt'];
    }

    setup() {
        super.setup();

        this.listenTo(this.model, 'change:startAt', () => {
            if (this.isRendered() && this.isReadMode()) {
                this.reRender();
            }
        });
    }

    data() {
        const data = super.data();

        const startAt = this.model.get('startAt');

        if (
            this.isReadMode() &&
            SENT_STATUSES.includes(this.model.get(this.name)) &&
            isFuture(this, startAt)
        ) {
            data.valueTranslated = this.translate('Scheduled', 'labels', 'SimpleMassEmail') + ' · ' +
                this.getDateTime().toDisplay(startAt);
            data.style = 'primary';
        }

        return data;
    }
}

export default SimpleMassEmailStatusFieldView;
