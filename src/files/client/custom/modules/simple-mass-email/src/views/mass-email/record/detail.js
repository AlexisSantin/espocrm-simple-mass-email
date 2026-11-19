/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 ************************************************************************/

import MassEmailDetailView from 'crm:views/mass-email/record/detail';
import Ui from 'ui';

// Statuses from which the mass email can be sent, or sent again after a suspension.
const SENDABLE_STATUSES = ['Draft', 'Failed'];

class SimpleMassEmailDetailView extends MassEmailDetailView {

    // The subject and body are stored in an EmailTemplate, so editing goes through the dedicated edit view.
    editModeDisabled = true;

    hasModifyDetailLayout = true;

    setup() {
        super.setup();

        if (
            SENDABLE_STATUSES.includes(this.model.get('status')) &&
            this.getAcl().checkModel(this.model, 'edit')
        ) {
            this.addButton({
                name: 'sendEmail',
                label: 'Send email',
                style: 'primary',
            }, true);
        }
    }

    actionSendEmail() {
        const message = this.model.get('emailAccountId') ?
            this.translate('confirmSendPersonal', 'messages', 'SimpleMassEmail')
                .replace('{limit}', this.model.get('dailyLimit')) :
            this.translate('confirmSend', 'messages', 'SimpleMassEmail');

        Ui.confirm(message, {
            confirmText: this.translate('Send email', 'labels', 'MassEmail'),
            cancelText: this.translate('Cancel', 'labels'),
            confirmStyle: 'primary',
        }).then(() => this.sendEmail());
    }

    async sendEmail() {
        Ui.notifyWait();

        // The native job sends Pending mass emails; a personal account turns it into a sending in batches.
        await this.model.save({status: 'Pending'}, {patch: true});

        Ui.success(this.translate('Saved'));

        this.removeButton('sendEmail');
    }

    modifyDetailLayout(layout) {
        super.modifyDetailLayout(layout);

        if (!this.model.get('emailAccountId')) {
            return;
        }

        layout.push({
            customLabel: this.translate('Personal sending', 'labels', 'SimpleMassEmail'),
            rows: [
                [
                    {name: 'dailyLimit'},
                    {name: 'sendingProgress'},
                ],
            ],
        });
    }
}

export default SimpleMassEmailDetailView;
