/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 ************************************************************************/

import MassEmailDetailView from 'crm:views/mass-email/record/detail';

class SimpleMassEmailDetailView extends MassEmailDetailView {

    // The subject and body are stored in an EmailTemplate, so editing goes through the dedicated edit view.
    editModeDisabled = true;

    hasModifyDetailLayout = true;

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
