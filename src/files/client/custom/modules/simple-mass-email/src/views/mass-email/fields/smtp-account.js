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
 * Adds the personal email accounts of the current user to the SMTP choice.
 */

import SmtpAccountFieldView from 'crm:views/mass-email/fields/smtp-account';
import Ajax from 'ajax';

const PREFIX = 'emailAccount:';

class SimpleMassEmailSmtpAccountFieldView extends SmtpAccountFieldView {

    getAttributeList() {
        return [...super.getAttributeList(), 'emailAccountId', 'emailAccountName'];
    }

    setup() {
        super.setup();

        const canEdit = this.getAcl().checkScope('MassEmail', 'create') ||
            this.getAcl().checkScope('MassEmail', 'edit');

        if (!this.isEditMode() || !canEdit) {
            return;
        }

        Ajax.getRequest('SimpleMassEmail/personalAccounts').then(list => {
            this.personalAccounts = list;

            this.setupOptions();
            this.reRender();
        });
    }

    setupOptions() {
        super.setupOptions();

        const currentId = this.model.get('emailAccountId');
        const accounts = this.personalAccounts || [];

        accounts.forEach(account => this.addPersonalOption(account.id, account.emailAddress));

        if (currentId && !accounts.some(account => account.id === currentId)) {
            this.addPersonalOption(currentId, this.model.get('emailAccountName') || currentId);
        }
    }

    addPersonalOption(id, label) {
        const key = PREFIX + id;

        this.params.options.push(key);
        this.translatedOptions[key] = `${label} (${this.translate('personal', 'labels', 'SimpleMassEmail')})`;
    }

    /**
     * @param {string} id
     * @return {{id: string, emailAddress: string, fromName: string}|undefined}
     */
    getPersonalAccount(id) {
        return (this.personalAccounts || []).find(account => account.id === id);
    }

    getValueForDisplay() {
        const id = this.model.get('emailAccountId');

        return id ? PREFIX + id : super.getValueForDisplay();
    }

    fetch() {
        const value = this.$element.val();

        if (!value || !value.startsWith(PREFIX)) {
            return {...super.fetch(), emailAccountId: null, emailAccountName: null};
        }

        const id = value.substring(PREFIX.length);

        return {
            [this.name]: value,
            inboundEmailId: null,
            inboundEmailName: null,
            emailAccountId: id,
            emailAccountName: this.getPersonalAccount(id)?.emailAddress || id,
        };
    }
}

export default SimpleMassEmailSmtpAccountFieldView;
