const LinksyAnchorCloud = ({
    name: 'LinksyAnchorCloud',
    props: {
        items: [Array, Object, null],
    },
    data() {
        return {
            to: LINKSY.admin_url+'?page=Linksy-anchor-cloud',
            summary: {
                total: '-',
                unique: '-',
                duplicate: '-'
            },
            setupStatus: LINKSY['status']
        }
    },
    watch: {
        items: function(val) {
            const total = Object.keys(val).length;
            const unique = Object.values(val).filter(e => e.length === 1).length;
            this.summary = {
                total: total,
                unique: unique,
                duplicate: total - unique
            }
        }
    },
    template: `
        <div class="card shadow linksy-anchor-cloud">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Anchor Cloud</h2>
                    <a class="button btn-app" :href="to" :disabled="setupStatus != 1">See all</a>
                </div>

                <div class="row">
                    <div class="col d-flex flex-column align-items-center">
                        <h1>{{summary.total}}</h1>
                        <span>Total Numbers</span>
                    </div>
                    <div class="col d-flex flex-column align-items-center border-left border-right">
                        <h1>{{summary.unique}}</h1>
                        <span>Unique</span>
                    </div>
                    <div class="col d-flex flex-column align-items-center">
                        <h1>{{summary.duplicate}}</h1>
                        <span>Duplicate</span>
                    </div>
                </div>
            </div>
        </div>
    `,
});