/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 ************************************************************************/

import MassEmailDetailView from 'crm:views/mass-email/record/detail';
import Ui from 'ui';
import {
    getSendingState,
    getSendNowMessage,
    getSentMessage,
    askSendingDate,
} from 'simple-mass-email:views/mass-email/helpers/sending';

// The sending actions available in each state.
const STATE_ACTIONS = {
    draft: ['sendNow', 'schedule'],
    failed: ['resume'],
    scheduled: ['sendNow', 'schedule', 'cancelSchedule'],
    running: ['pause'],
    done: [],
};

class SimpleMassEmailDetailView extends MassEmailDetailView {

    // The subject and body are stored in an EmailTemplate, so editing goes through the dedicated edit view.
    editModeDisabled = true;

    hasModifyDetailLayout = true;

    setup() {
        super.setup();

        if (!this.getAcl().checkModel(this.model, 'edit')) {
            return;
        }

        const buttons = [
            {name: 'sendNow', label: 'Send now', style: 'primary'},
            {name: 'resume', label: 'Resume sending', style: 'primary'},
            {name: 'schedule', label: 'Schedule…'},
            {name: 'cancelSchedule', label: 'Cancel schedule'},
            {name: 'pause', label: 'Pause'},
        ];

        // Added hidden, then shown according to the state, before the native buttons.
        buttons.reverse().forEach(item => {
            this.addButton({
                name: item.name,
                text: this.translate(item.label, 'labels', 'SimpleMassEmail'),
                style: item.style,
                hidden: true,
            }, true);
        });

        this.updateSendingButtons();

        this.listenTo(this.model, 'sync', () => this.updateSendingButtons());
    }

    updateSendingButtons() {
        const visible = STATE_ACTIONS[getSendingState(this, this.model)] || [];

        ['sendNow', 'resume', 'schedule', 'cancelSchedule', 'pause'].forEach(name => {
            visible.includes(name) ? this.showActionItem(name) : this.hideActionItem(name);
        });
    }

    getPersonalLimit() {
        return this.model.get('emailAccountId') ? this.model.get('dailyLimit') : null;
    }

    actionSendNow() {
        Ui.confirm(getSendNowMessage(this, this.getPersonalLimit()), {
            confirmText: this.translate('Send now', 'labels', 'SimpleMassEmail'),
            cancelText: this.translate('Cancel', 'labels'),
            confirmStyle: 'primary',
        }).then(() => this.saveSending({status: 'Pending', startAt: null}, getSentMessage(this, null)));
    }

    actionResume() {
        this.actionSendNow();
    }

    async actionSchedule() {
        const startAt = await askSendingDate(this, this.model.get('startAt'));

        if (startAt) {
            await this.saveSending({status: 'Pending', startAt: startAt}, getSentMessage(this, startAt));
        }
    }

    actionCancelSchedule() {
        this.saveSending({status: 'Draft'}, this.translate('scheduleCancelled', 'messages', 'SimpleMassEmail'));
    }

    actionPause() {
        this.saveSending({status: 'Draft'}, this.translate('sendingPaused', 'messages', 'SimpleMassEmail'));
    }

    /**
     * Pending is handed to the extension's job, Draft stops it: the queue already built is kept.
     */
    async saveSending(attributes, message) {
        Ui.notifyWait();

        try {
            await this.model.save(attributes, {patch: true});
        }
        catch (e) {
            return;
        }

        Ui.success(message);
    }

    modifyDetailLayout(layout) {
        super.modifyDetailLayout(layout);

        if (this.model.get('emailAccountId')) {
            layout.push({
                customLabel: this.translate('Personal sending', 'labels', 'SimpleMassEmail'),
                rows: [
                    [
                        {name: 'dailyLimit'},
                        {name: 'sendingProgress'},
                    ],
                ],
            });

            return;
        }

        if (this.model.get('sendingProgress')) {
            layout.push({
                customLabel: this.translate('Sending', 'labels', 'SimpleMassEmail'),
                rows: [
                    [
                        {name: 'sendingProgress'},
                        false,
                    ],
                ],
            });
        }
    }
}

export default SimpleMassEmailDetailView;
