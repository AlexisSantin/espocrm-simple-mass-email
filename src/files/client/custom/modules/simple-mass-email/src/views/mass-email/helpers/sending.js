/************************************************************************
 * SimpleMassEmail – extension for EspoCRM.
 * Sending state of a mass email, shared by the form and the detail view.
 ************************************************************************/

// Statuses of a mass email handed over for sending (Pending and In Process are the native ones).
export const SENT_STATUSES = ['Pending', 'In Process', 'Sending', 'Throttled'];

/**
 * @param {import('view').default} view
 * @param {import('model').default} model
 * @return {'new'|'draft'|'failed'|'scheduled'|'running'|'done'}
 */
export function getSendingState(view, model) {
    if (model.isNew()) {
        return 'new';
    }

    const status = model.get('status');

    if (status === 'Draft') {
        return 'draft';
    }

    if (status === 'Failed') {
        return 'failed';
    }

    if (SENT_STATUSES.includes(status)) {
        return isFuture(view, model.get('startAt')) ? 'scheduled' : 'running';
    }

    return 'done';
}

/**
 * @param {import('view').default} view
 * @param {?string} dateTime A system date-time (UTC).
 */
export function isFuture(view, dateTime) {
    if (!dateTime) {
        return false;
    }

    const dateTimeUtil = view.getDateTime();

    return dateTimeUtil.toMoment(dateTime).isAfter(dateTimeUtil.getNowMoment());
}

/**
 * The confirmation shown before sending now.
 *
 * @param {import('view').default} view
 * @param {?number} limit The limit per 24 hours of a personal account.
 */
export function getSendNowMessage(view, limit) {
    if (limit) {
        return view.translate('confirmSendPersonal', 'messages', 'SimpleMassEmail').replace('{limit}', limit);
    }

    return view.translate('confirmSend', 'messages', 'SimpleMassEmail');
}

/**
 * The message shown once the sending is handed over.
 *
 * @param {import('view').default} view
 * @param {?string} startAt A planned date, or null for now.
 */
export function getSentMessage(view, startAt) {
    if (!startAt) {
        return view.translate('sendStarted', 'messages', 'SimpleMassEmail');
    }

    return view.translate('sendScheduled', 'messages', 'SimpleMassEmail')
        .replace('{date}', view.getDateTime().toDisplay(startAt));
}

/**
 * Asks for a sending date. Resolves with a system date-time (UTC), or null if cancelled.
 *
 * @param {import('view').default} view
 * @param {?string} current The date already planned.
 * @return {Promise<?string>}
 */
export async function askSendingDate(view, current) {
    const modal = await view.createView('scheduleModal', 'simple-mass-email:views/mass-email/modals/schedule', {
        startAt: isFuture(view, current) ? current : null,
    });

    return new Promise(resolve => {
        view.listenToOnce(modal, 'schedule', startAt => resolve(startAt));
        view.listenToOnce(modal, 'close', () => resolve(null));

        modal.render();
    });
}
