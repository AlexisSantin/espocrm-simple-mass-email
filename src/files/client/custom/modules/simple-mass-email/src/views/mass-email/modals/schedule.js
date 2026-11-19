/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Choice of the date at which a mass email is sent.
 ************************************************************************/

import ModalView from 'views/modal';
import Model from 'model';
import EditForModalRecordView from 'views/record/edit-for-modal';
import DatetimeFieldView from 'views/fields/datetime';

const DEFAULT_HOUR = 9;

class SimpleMassEmailScheduleModalView extends ModalView {

    // language=Handlebars
    templateContent = `
        <p class="text-muted">{{translate 'scheduleHint' category='messages' scope='SimpleMassEmail'}}</p>
        <div class="record-container no-side-margin">{{{record}}}</div>
    `;

    /**
     * @param {{startAt?: ?string}} options
     */
    constructor(options) {
        super(options);

        this.startAt = options.startAt || null;
    }

    setup() {
        super.setup();

        this.headerText = this.translate('Schedule sending', 'labels', 'SimpleMassEmail');

        this.formModel = new Model();
        this.formModel.set('startAt', this.startAt || this.getDefaultStartAt());

        this.recordView = new EditForModalRecordView({
            model: this.formModel,
            detailLayout: [
                {
                    rows: [
                        [
                            {
                                view: new DatetimeFieldView({
                                    name: 'startAt',
                                    labelText: this.translate('startAt', 'fields', 'MassEmail'),
                                    mode: 'edit',
                                    params: {required: true},
                                }),
                            },
                            false,
                        ],
                    ],
                },
            ],
        });

        this.assignView('record', this.recordView);

        this.buttonList.push({
            name: 'schedule',
            text: this.translate('Schedule', 'labels', 'SimpleMassEmail'),
            style: 'primary',
            onClick: () => this.actionSchedule(),
        });

        this.buttonList.push({
            name: 'cancel',
            label: 'Cancel',
            onClick: () => this.actionClose(),
        });
    }

    /**
     * Tomorrow morning, in the user's time zone.
     */
    getDefaultStartAt() {
        return this.getDateTime().getNowMoment()
            .add(1, 'day')
            .hours(DEFAULT_HOUR)
            .minutes(0)
            .seconds(0)
            .utc()
            .format('YYYY-MM-DD HH:mm:ss');
    }

    actionSchedule() {
        if (this.recordView.validate()) {
            return;
        }

        const startAt = this.formModel.get('startAt');
        const dateTime = this.getDateTime();

        if (!dateTime.toMoment(startAt).isAfter(dateTime.getNowMoment())) {
            Espo.Ui.error(this.translate('scheduleInPast', 'messages', 'SimpleMassEmail'));

            return;
        }

        this.trigger('schedule', startAt);
        this.close();
    }
}

export default SimpleMassEmailScheduleModalView;
