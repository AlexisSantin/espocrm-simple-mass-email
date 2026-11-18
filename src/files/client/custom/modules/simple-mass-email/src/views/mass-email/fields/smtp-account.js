/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Adds the personal email accounts of the current user to the SMTP choice.
 ************************************************************************/

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
