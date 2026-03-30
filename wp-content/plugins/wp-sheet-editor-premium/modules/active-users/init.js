document.addEventListener('alpine:init', () => {
    Alpine.store('activeUsers', {
        users: [],
        showPopupOnce: vgse_editor_settings.active_users_tracking === '',
        modalOpened: false,

        updateUsers(newUsers) {
            this.users = newUsers;

            // Auto open modal if there are more than 1 user and it's the first time
            if (this.showPopupOnce && !this.modalOpened && this.users.length > 1) {
                const modal = jQuery('[data-remodal-id="active-users-modal"]');
                if (modal.length > 0 && typeof modal.remodal === 'function') {
                    modal.remodal().open();
                    this.modalOpened = true;
                }
            }
        }
    });
});

jQuery(document).ready(function ($) {
    if (typeof vgse_editor_settings.active_users_tracking !== 'string' ||
        vgse_editor_settings.active_users_tracking === 'disabled' || typeof wp.heartbeat === 'undefined') {
        return;
    }

    const sheet_key = vgse_editor_settings.post_type;

    // Inject toolbar container with Alpine directives
    const toolbar = $('.vg-secondary-toolbar .vg-header-toolbar-inner');
    if (toolbar.length > 0) {
        const usersContainer = $(`
            <div class="viewer-users-container button-container" x-data>
                <template x-for="user in $store.activeUsers.users" :key="user.user_login">
                    <span class="viewer-user-icon" data-wpse-tooltip="down" :aria-label="user.user_login + ' - ' + user.last_active">
                        <template x-if="user.avatar">
                            <img :src="user.avatar" :alt="user.user_login">
                        </template>
                        <template x-if="!user.avatar">
                            <i class="fa fa-user"></i>
                        </template>
                    </span>
                </template>
            </div>
        `);

        if (toolbar.children('.button-container:last').length) {
            toolbar.children('.button-container:last').after(usersContainer);
        } else {
            toolbar.append(usersContainer);
        }
    }

    // Send heartbeat data to server
    $(document).on('heartbeat-send', function (event, data) {
        data.vgse_track_viewer = true;
        data.vgse_track_viewer_sheet_key = sheet_key;
        data.vgse_track_viewer_nonce = vgse_editor_settings.nonce;
    });

    // Handle heartbeat response from server and update the Alpine store
    $(document).on('heartbeat-tick', function (event, data) {
        if (data.vgse_active_users && data.vgse_active_users.success) {
            Alpine.store('activeUsers').updateUsers(data.vgse_active_users.data.users);
        }
    });

    // Initial tracking
    setTimeout(() => {
        // Trigger heartbeat immediately
        if (wp.heartbeat) {
            wp.heartbeat.connectNow();
        }
    }, 2000);
});