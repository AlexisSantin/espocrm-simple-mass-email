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
 * Sending state of a mass email, shared by the form and the detail view.
 */

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
