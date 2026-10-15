/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Overrides the MassEmail edit view: the email (subject and body) is
 * written in the view itself, parameters are displayed in the right column.
 * The content is stored in an EmailTemplate flagged as "oneOff".
 ************************************************************************/

import MassEmailEditView from 'crm:views/mass-email/record/edit';
import Ajax from 'ajax';
import Ui from 'ui';

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
                            <label class="control-label">{{translate 'startAt' category='fields' scope='MassEmail'}}</label>
                            <div class="field" data-name="startAt"></div>
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
                        <p class="text-muted">
                            {{translate 'Test email will be sent to' category='labels' scope='SimpleMassEmail'}}
                            <strong>{{testAddress}}</strong>
                        </p>
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

    buttonList = [
        {
            name: 'sendEmail',
            label: 'Send email',
            style: 'primary',
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

    setup() {
        this.detailLayout = [];

        super.setup();

        this.templateModel = null;
        this.templateSnapshot = null;
        this.ownTemplateId = null;
        this.linkedTemplate = null;
        this.isSendRequested = false;

        if (this.model.isNew()) {
            // The native default status is "Pending", which would queue the email for sending on save.
            this.model.set('status', 'Draft');
        }

        this.addActionHandler('sendTest', () => this.actionSendTest());

        this.createField('name', null, {required: true});

        this.fieldNameList = [
            'status',
            'campaign',
            'targetLists',
            'excludingTargetLists',
            'startAt',
            'storeSentEmails',
            'optOutEntirely',
            'smtpAccount',
            'fromAddress',
            'fromName',
            'replyToAddress',
            'replyToName',
        ];

        this.fieldNameList.forEach(name => this.createField(name));

        this.createEmailTemplateField();

        this.ownFieldNameList = ['name', 'emailTemplate', ...this.fieldNameList];

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
    }

    data() {
        const data = super.data();

        data.testAddress = this.getUser().get('emailAddress') || '';

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

        if (this.isSendRequested) {
            data.status = 'Pending';
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

    actionSendEmail() {
        const message = this.translate('confirmSend', 'messages', 'SimpleMassEmail');

        Ui.confirm(message, {
            confirmText: this.translate('Send email', 'labels', 'MassEmail'),
            cancelText: this.translate('Cancel', 'labels'),
            confirmStyle: 'primary',
        }).then(() => this.sendEmail());
    }

    async sendEmail() {
        this.isSendRequested = true;

        try {
            await this.save();
        }
        catch (reason) {
            this.isSendRequested = false;

            return;
        }

        this.isSendRequested = false;

        this.exit(this.model.isNew() ? 'create' : 'save');
    }

    async actionSendTest() {
        try {
            await this.save();
        }
        catch (reason) {
            if (reason !== 'notModified') {
                return;
            }
        }

        const user = this.getUser();
        const address = user.get('emailAddress');

        if (!address) {
            Ui.error(this.translate('noTestAddress', 'messages', 'SimpleMassEmail'));

            return;
        }

        try {
            await Ajax.postRequest('MassEmail/action/sendTest', {
                id: this.model.id,
                targetList: [{id: user.id, type: 'User'}],
            });

            Ui.success(this.translate('testEmailSent', 'messages', 'SimpleMassEmail') + ' ' + address);
        }
        catch (e) {
            Ui.error(this.translate('testEmailError', 'messages', 'SimpleMassEmail'));
        }
    }
}

export default SimpleMassEmailEditView;
