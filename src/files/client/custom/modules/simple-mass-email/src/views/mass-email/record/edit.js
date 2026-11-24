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
 * Overrides the MassEmail edit view: the email (subject and body) is
 * written in the view itself, parameters are displayed in the right column.
 * The content is stored in an EmailTemplate flagged as "oneOff".
 */

import MassEmailEditView from 'crm:views/mass-email/record/edit';
import Ui from 'ui';
import {
    getSendingState,
    getSendNowMessage,
    getSentMessage,
    askSendingDate,
} from 'simple-mass-email:views/mass-email/helpers/sending';

const DEFAULT_DAILY_LIMIT = 45;

class SimpleMassEmailEditView extends MassEmailEditView {

    // language=Handlebars
    templateContent = `
        <div class="edit" id="{{id}}" data-scope="{{scope}}" tabindex="-1">
            <div class="detail-button-container button-container record-buttons">
                <div class="sub-container clearfix">
                    <div class="btn-group actions-btn-group" role="group">{{{buttons}}}</div>
                    <div class="btn-group pull-right" role="group">{{{sideButtons}}}</div>
                </div>
            </div>

            <div class="simple-mass-email-grid">
                <div class="simple-mass-email-main">
                    <div class="simple-mass-email-block">
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'name' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="name"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'status' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="status"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'smtpAccount' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="smtpAccount"></div>
                        </div>
                        <div class="simple-mass-email-row hidden" data-role="daily-limit">
                            <label class="control-label">{{translate 'dailyLimit' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="dailyLimit"></div>
                            <p class="text-muted small simple-mass-email-hint">
                                {{translate 'dailyLimitHint' category='labels' scope='SimpleMassEmail'}}
                            </p>
                        </div>
                        <div class="simple-mass-email-pair">
                            <div class="simple-mass-email-row">
                                <label class="control-label">{{translate 'fromAddress' category='fields' scope='MassEmail'}}</label>
                                <div class="field" data-name="fromAddress"></div>
                            </div>
                            <div class="simple-mass-email-row">
                                <label class="control-label">{{translate 'fromName' category='fields' scope='MassEmail'}}</label>
                                <div class="field" data-name="fromName"></div>
                            </div>
                            <div class="simple-mass-email-row">
                                <label class="control-label">{{translate 'replyToAddress' category='fields' scope='MassEmail'}}</label>
                                <div class="field" data-name="replyToAddress"></div>
                            </div>
                            <div class="simple-mass-email-row">
                                <label class="control-label">{{translate 'replyToName' category='fields' scope='MassEmail'}}</label>
                                <div class="field" data-name="replyToName"></div>
                            </div>
                        </div>
                    </div>

                    <div class="simple-mass-email-block">
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'emailTemplate' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="emailTemplate"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'subject' category='fields' scope='EmailTemplate'}}</label>
                            <div class="field" data-name="subject"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'body' category='fields' scope='EmailTemplate'}}</label>
                            <div class="field" data-name="body"></div>
                        </div>
                    </div>
                </div>

                <div class="simple-mass-email-side">
                    <div class="simple-mass-email-block">
                        <h4 class="simple-mass-email-block-title">{{translate 'Parameters' category='labels' scope='SimpleMassEmail'}}</h4>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'campaign' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="campaign"></div>
                            {{#if campaignRequired}}
                            <p class="text-muted small simple-mass-email-hint">
                                {{translate 'campaignRequiredHint' category='labels' scope='SimpleMassEmail'}}
                            </p>
                            {{/if}}
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'targetLists' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="targetLists"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'excludingTargetLists' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="excludingTargetLists"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'storeSentEmails' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="storeSentEmails"></div>
                        </div>
                        <div class="simple-mass-email-row">
                            <label class="control-label">{{translate 'optOutEntirely' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="optOutEntirely"></div>
                        </div>
                    </div>

                    <div class="simple-mass-email-block">
                        <h4 class="simple-mass-email-block-title">{{translate 'Test email' category='labels' scope='SimpleMassEmail'}}</h4>
                        <p class="text-muted">{{translate 'testHint' category='labels' scope='SimpleMassEmail'}}</p>
                        <button class="btn btn-default btn-block" data-action="sendTest">
                            {{translate 'Send test email' category='labels' scope='SimpleMassEmail'}}
                        </button>
                    </div>

                    <div class="simple-mass-email-block">
                        <h4 class="simple-mass-email-block-title">{{translate 'Placeholders' category='labels' scope='SimpleMassEmail'}}</h4>
                        <ul class="list-unstyled small">
                            <li><code>\\{{Contact.name}}</code></li>
                            <li><code>\\{{Account.name}}</code></li>
                            <li><code>\\{{today}}</code></li>
                            <li><code>{optOutLink}</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="hidden middle">{{{middle}}}</div>
        </div>
    `;

    // The fields are declared by this view only: the native layout would duplicate them.
    sideDisabled = true;
    bottomDisabled = true;

    // The native middle panel is hidden here: measured from it, the button bar would be hidden as
    // soon as the page scrolls. It follows the form instead, down to its end.
    stickButtonsFormBottomSelector = '.simple-mass-email-grid';
    stickButtonsContainerAllTheWay = true;

    setup() {
        this.detailLayout = [];

        // Sending is chosen with a button, not by editing the status and the start date.
        this.buttonList = [
            {
                name: 'sendNow',
                text: this.translate('Send now', 'labels', 'SimpleMassEmail'),
                style: 'primary',
            },
            {
                name: 'schedule',
                text: this.translate('Schedule…', 'labels', 'SimpleMassEmail'),
            },
            {
                name: 'save',
                label: 'Save',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        if (!['new', 'draft', 'failed', 'scheduled'].includes(getSendingState(this, this.model))) {
            // Being sent or finished: nothing more to send from here.
            this.buttonList = this.buttonList.filter(item => !['sendNow', 'schedule'].includes(item.name));
        }

        super.setup();

        this.templateModel = null;
        this.templateSnapshot = null;
        this.ownTemplateId = null;
        this.linkedTemplate = null;
        this.sendRequest = null;

        if (this.model.isNew()) {
            // The native default status is "Pending", which would queue the email for sending on save.
            this.model.set('status', 'Draft');
        }

        this.addActionHandler('sendTest', () => this.actionSendTest());

        this.createField('name', null, {required: true});

        // Shown only: it changes with the sending buttons.
        this.createField('status', null, null, 'detail', true);

        // Without a campaign, a mass email belongs to nobody: a role that edits only its own
        // campaigns, or its team's, can then neither create nor change it.
        this.createField('campaign', 'simple-mass-email:views/mass-email/fields/campaign', {
            required: this.isCampaignRequired(),
        });

        this.fieldNameList = [
            'targetLists',
            'excludingTargetLists',
            'storeSentEmails',
            'optOutEntirely',
            'smtpAccount',
            'fromAddress',
            'fromName',
            'replyToAddress',
            'replyToName',
            'dailyLimit',
        ];

        this.fieldNameList.forEach(name => {
            // The sender of a personal account is the account itself.
            const readOnly = name === 'fromAddress' && this.isPersonal();

            // A number field cannot go through the parent's render: its input is not there yet.
            // Like the others, it is rendered by afterRender.
            this.createField(name, null, null, 'edit', readOnly, {notToRender: name === 'dailyLimit'});
        });

        this.listenTo(this.model, 'change:emailAccountId', (model, value, options) => {
            if (options.ui) {
                this.onPersonalAccountChange();
            }
        });

        this.createEmailTemplateField();

        this.ownFieldNameList = ['name', 'status', 'campaign', 'emailTemplate', ...this.fieldNameList];

        this.wait(this.loadTemplate());
    }

    getFieldViews(withHidden) {
        const fields = super.getFieldViews(withHidden);

        this.ownFieldNameList.forEach(name => {
            const view = this.getView(name + 'Field');

            if (view) {
                fields[name] = view;
            }
        });

        return fields;
    }

    getFieldView(name) {
        return this.getView(name + 'Field') || super.getFieldView(name);
    }

    afterRender() {
        super.afterRender();

        // Nested views are not rendered with the parent template, so they are rendered explicitly.
        this.ownFieldNameList.forEach(name => {
            const view = this.getView(name + 'Field');

            if (view) {
                view.render();
            }
        });

        ['templateSubject', 'templateBody'].forEach(key => {
            const view = this.getView(key);

            if (view) {
                view.render();
            }
        });

        this.updateDailyLimitRow();
    }

    isPersonal() {
        return !!this.model.get('emailAccountId');
    }

    isCampaignRequired() {
        return !this.getUser().isAdmin() && this.getAcl().getLevel('Campaign', 'edit') !== 'all';
    }

    updateDailyLimitRow() {
        const isPersonal = this.isPersonal();

        this.element.querySelector('[data-role="daily-limit"]')?.classList.toggle('hidden', !isPersonal);

        const view = this.getView('dailyLimitField');

        if (view) {
            view.params.required = isPersonal;
        }
    }

    async onPersonalAccountChange() {
        const fromAddressView = this.getView('fromAddressField');
        const id = this.model.get('emailAccountId');

        this.updateDailyLimitRow();

        if (!id) {
            fromAddressView.setNotReadOnly();

            await fromAddressView.setEditMode();
            await fromAddressView.reRender();

            return;
        }

        const account = this.getView('smtpAccountField').getPersonalAccount(id);

        this.model.set({
            fromAddress: account?.emailAddress,
            fromName: account?.fromName || this.model.get('fromName'),
            dailyLimit: this.model.get('dailyLimit') || DEFAULT_DAILY_LIMIT,
        });

        await fromAddressView.setReadOnly();
    }

    data() {
        const data = super.data();

        data.campaignRequired = this.isCampaignRequired();

        return data;
    }

    async loadTemplate() {
        const emailTemplateId = this.model.get('emailTemplateId');

        try {
            if (emailTemplateId) {
                const template = await this.fetchEmailTemplate(emailTemplateId);

                if (template.get('oneOff')) {
                    this.ownTemplateId = template.id;
                }

                await this.useTemplate(template);
            }
            else {
                await this.setTemplateModel({});
            }
        }
        catch (e) {
            Ui.error(this.translate('templateLoadError', 'messages', 'SimpleMassEmail'));

            await this.setTemplateModel({});
        }
    }

    async fetchEmailTemplate(id) {
        const template = await this.getModelFactory().create('EmailTemplate');

        template.id = id;

        await template.fetch();

        return template;
    }

    async useTemplate(template) {
        const attributes = this.extractTemplateAttributes(template);

        await this.setTemplateModel(attributes, {
            ownId: template.id === this.ownTemplateId ? template.id : null,
            snapshot: attributes,
        });
    }

    async onEmailTemplateChange() {
        const {emailTemplateId} = this.getView('emailTemplateField').fetch();

        if (!emailTemplateId) {
            // The content on screen is kept: it is saved as a private copy.
            await this.setTemplateModel(this.getTemplateContent());

            return;
        }

        try {
            await this.useTemplate(await this.fetchEmailTemplate(emailTemplateId));
        }
        catch (e) {
            Ui.error(this.translate('templateLoadError', 'messages', 'SimpleMassEmail'));
        }
    }

    getTemplateContent() {
        return {
            ...this.getView('templateSubject').fetch(),
            ...this.getView('templateBody').fetch(),
        };
    }

    async setTemplateModel(content, {ownId = null, snapshot = null} = {}) {
        const model = await this.getModelFactory().create('EmailTemplate');

        if (ownId) {
            model.id = ownId;
        }

        model.set({...content, isHtml: true, oneOff: true});

        this.templateModel = model;
        this.templateSnapshot = snapshot;
        this.linkedTemplate = null;

        this.createTemplateViews();
    }

    createEmailTemplateField() {
        this.createView('emailTemplateField', this.model.getFieldParam('emailTemplate', 'view'), {
            model: this.model,
            mode: 'edit',
            selector: '.field[data-name="emailTemplate"]',
            defs: {
                name: 'emailTemplate',
                params: {required: false},
            },
        }, view => {
            this.listenTo(view, 'change', () => this.onEmailTemplateChange());

            if (this.isRendered()) {
                view.render();
            }
        });
    }

    extractTemplateAttributes(model) {
        return {
            name: model.get('name'),
            subject: model.get('subject'),
            body: model.get('body'),
            bodyPlain: model.get('bodyPlain'),
            isHtml: true,
            oneOff: true,
        };
    }

    createTemplateViews() {
        this.createTemplateView('templateSubject', 'subject', {required: true});
        this.createTemplateView('templateBody', 'body', {});
    }

    createTemplateView(key, name, params) {
        const viewName = this.templateModel.getFieldParam(name, 'view') ||
            this.getFieldManager().getViewName(this.templateModel.getFieldType(name));

        this.createView(key, viewName, {
            model: this.templateModel,
            mode: 'edit',
            selector: `.field[data-name="${name}"]`,
            defs: {
                name: name,
                params: params,
            },
        }, view => {
            if (this.isRendered()) {
                view.render();
            }
        });
    }

    fetch() {
        const data = super.fetch();

        // Content is stored in the EmailTemplate, not in the MassEmail.
        delete data.subject;
        delete data.body;

        if (this.linkedTemplate) {
            data.emailTemplateId = this.linkedTemplate.id;
            data.emailTemplateName = this.linkedTemplate.name;
        }

        // The status is changed only by the sending buttons.
        delete data.status;

        if (this.sendRequest) {
            // Pending is handed to the extension's job; an empty date means now.
            data.status = 'Pending';
            data.startAt = this.sendRequest.startAt;
        }

        return data;
    }

    async save(options) {
        const subjectView = this.getView('templateSubject');
        const bodyView = this.getView('templateBody');

        if (!this.templateModel || !subjectView || !bodyView) {
            throw 'notReady';
        }

        const fetched = this.fetch();

        this.model.setMultiple(fetched, {silent: true});

        if (this.validate() || subjectView.validate()) {
            this.afterNotValid();

            throw 'invalid';
        }

        const subjectData = subjectView.fetch();
        const bodyData = bodyView.fetch();

        const templateAttributes = {
            name: this.model.get('name'),
            subject: subjectData.subject,
            body: bodyData.body,
            bodyPlain: bodyData.bodyPlain,
            isHtml: true,
            oneOff: true,
        };

        if (this.templateModel.isNew()) {
            // Owned by its author: roles that read only their own templates, or assign only within
            // their teams, would otherwise refuse to create it or to link it to the mass email.
            templateAttributes.assignedUserId = this.getUser().id;
            templateAttributes.assignedUserName = this.getUser().get('name');

            // Like a record created in EspoCRM's own forms.
            const defaultTeamId = this.getUser().get('defaultTeamId');

            if (defaultTeamId) {
                templateAttributes.teamsIds = [defaultTeamId];
                templateAttributes.teamsNames = {[defaultTeamId]: this.getUser().get('defaultTeamName')};
            }
        }

        let templateSaved = false;

        if (this.isTemplateChanged(templateAttributes)) {
            this.templateModel.set(templateAttributes);

            try {
                await this.templateModel.save(templateAttributes, {patch: !this.templateModel.isNew()});
            }
            catch (e) {
                Ui.error(this.translate('templateSaveError', 'messages', 'SimpleMassEmail'));

                throw 'error';
            }

            this.templateSnapshot = this.extractTemplateAttributes(this.templateModel);
            this.linkedTemplate = {id: this.templateModel.id, name: this.templateModel.get('name')};
            templateSaved = true;
        }

        try {
            await super.save({...options, skipNotModifiedWarning: templateSaved});
        }
        catch (reason) {
            if (reason === 'notModified' && templateSaved) {
                this.afterSave();

                return;
            }

            throw reason;
        }
    }

    isTemplateChanged(attributes) {
        const snapshot = this.templateSnapshot;

        if (!snapshot) {
            return true;
        }

        // A shared template is only compared on its content, so that it is never copied by mistake.
        // bodyPlain is derived from body and is not compared.
        const keys = this.templateModel.id ? ['name', 'subject', 'body'] : ['subject', 'body'];

        return keys.some(key => attributes[key] !== snapshot[key]);
    }

    actionSendNow() {
        const limit = this.isPersonal() ? this.getView('dailyLimitField').fetch().dailyLimit : null;

        Ui.confirm(getSendNowMessage(this, limit), {
            confirmText: this.translate('Send now', 'labels', 'SimpleMassEmail'),
            cancelText: this.translate('Cancel', 'labels'),
            confirmStyle: 'primary',
        }).then(() => this.send(null));
    }

    async actionSchedule() {
        const startAt = await askSendingDate(this, this.model.get('startAt'));

        if (startAt) {
            await this.send(startAt);
        }
    }

    /**
     * @param {?string} startAt A date, or null for now.
     */
    async send(startAt) {
        const isNew = this.model.isNew();

        this.sendRequest = {startAt: startAt};

        try {
            await this.save();
        }
        catch (reason) {
            return;
        }
        finally {
            this.sendRequest = null;
        }

        Ui.success(getSentMessage(this, startAt));

        this.exit(isNew ? 'create' : 'save');
    }

    /**
     * The native test: the recipients are chosen among users, contacts, leads and accounts.
     */
    async actionSendTest() {
        try {
            await this.save();
        }
        catch (reason) {
            if (reason !== 'notModified') {
                return;
            }
        }

        const view = await this.createView('sendTestModal', 'crm:views/mass-email/modals/send-test', {
            model: this.model,
        });

        await view.render();
    }
}

export default SimpleMassEmailEditView;
