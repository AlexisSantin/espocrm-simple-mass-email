/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 ************************************************************************/

import MassEmailDetailView from 'crm:views/mass-email/record/detail';

class SimpleMassEmailDetailView extends MassEmailDetailView {

    // The subject and body are stored in an EmailTemplate, so editing goes through the dedicated edit view.
    editModeDisabled = true;
}

export default SimpleMassEmailDetailView;
