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
 * Campaign of the mass email: a role that edits only its own campaigns can pick only those.
 */

import LinkFieldView from 'views/fields/link';

class SimpleMassEmailCampaignFieldView extends LinkFieldView {

    getSelectBoolFilterList() {
        if (this.getAcl().getLevel('Campaign', 'edit') === 'own') {
            return ['onlyMy'];
        }

        return super.getSelectBoolFilterList();
    }
}

export default SimpleMassEmailCampaignFieldView;
