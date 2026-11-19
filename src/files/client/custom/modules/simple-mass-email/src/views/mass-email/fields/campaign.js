/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Campaign of the mass email: a role that edits only its own campaigns can pick only those.
 ************************************************************************/

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
