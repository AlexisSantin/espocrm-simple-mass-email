/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Progress of a mass email sent in batches through a personal account.
 ************************************************************************/

import BaseFieldView from 'views/fields/base';

class SimpleMassEmailSendingProgressFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#each lines}}<div>{{this}}</div>{{/each}}
    `;

    data() {
        return {...super.data(), lines: this.getLines()};
    }

    getLines() {
        const progress = this.model.get(this.name);

        if (!progress) {
            return [];
        }

        const lines = [
            this.format('progressCounts', progress),
            this.format('progressWindow', progress),
        ];

        if (this.model.get('status') !== 'Throttled' || !progress.pending) {
            return lines;
        }

        if (progress.nextSlotAt) {
            lines.push(this.format('progressNext', {
                date: this.getDateTime().toDisplay(progress.nextSlotAt),
            }));
        }

        lines.push(this.format('progressDays', progress));

        return lines;
    }

    format(key, values) {
        let text = this.translate(key, 'labels', 'SimpleMassEmail');

        Object.keys(values).forEach(name => {
            text = text.replace(`{${name}}`, values[name]);
        });

        return text;
    }
}

export default SimpleMassEmailSendingProgressFieldView;
