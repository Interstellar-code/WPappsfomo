const LinksyLinkStats = ({
    name: 'LinksyLinkStats',
    props: {
        broken: null,
        orphaned: null,
        internal: null,
        external: null,

    },
    data() {
        return {
            to: LINKSY.admin_url+'?page=Linksy-reports'
        }
    },
    computed: {
        total: {
            get() {
                const total = this.internal + this.external;

                return isNaN(total)? '-' : total;
            }
        }
    },
    template: `
        <div class="card shadow linksy-post-stats">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Link Stats</h2>
                    <a class="button btn-app" :href="to">See all</a>
                </div>

                <div class="linksy-post-stats-items">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="cube"><i class="fa fa-link"></i></div>
                            <span>Links Found</span>
                        </div>
                        <span>{{total}}</span>
                    </div>

                    <br />

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="cube"><i class="fa fa-arrows-down-to-line"></i></div>
                            <span>Internal Links</span>
                        </div>
                        <span>{{internal || '-'}}</span>
                    </div>

                    <br />

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="cube"><i class="fa fa-arrow-up-right-from-square"></i></div>
                            <span>External Links</span>
                        </div>
                        <span>{{external || '-'}}</span>
                    </div>

                    <br />

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="cube"><i class="fa fa-arrows-split-up-and-left"></i></div>
                            <span>Orphaned Posts</span>
                        </div>
                        <span>{{orphaned || '-'}}</span>
                    </div>
                </div>
            </div>
        </div>
    `,
});
