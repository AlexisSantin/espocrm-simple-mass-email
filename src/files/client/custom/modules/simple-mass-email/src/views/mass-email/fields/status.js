/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Shows a mass email planned for later as "Scheduled", with its date.
 ************************************************************************/

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
