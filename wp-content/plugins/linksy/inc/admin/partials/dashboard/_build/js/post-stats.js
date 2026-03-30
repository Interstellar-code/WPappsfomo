const LinksyPostStats = ({
    name: 'LinksyPostStats',
    props: {
        failed: null,
        synced: null,
        published: null,
        invalid: false
    },
    methods: {
        async reloadPostSummary() {
            try {
                const {data: {data}} = await axios.get(ajaxurl, {
                    params: {
                        action: 'linksy_dashboard_sync_posts',
                        nonce: LINKSY_SECURE_TOKEN,
                    }
                });

                this.$emit('onRefresh');

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
    },
    template: `
        <div class="card shadow linksy-post-stats">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Post Stats</h2>
                    <button class="button btn-app" :disabled="!invalid" @click="reloadPostSummary">Refresh</button>
                </div>

                <div class="row">
                    <div class="col d-flex flex-column align-items-center">
                        <h1>{{published}}</h1>
                        <span>Total published</span>
                    </div>
                    <div class="col d-flex flex-column align-items-center border-left border-right">
                        <h1>{{synced}}</h1>
                        <span>Crawled and synced</span>
                    </div>
                    <div class="col d-flex flex-column align-items-center">
                        <h1>{{failed}}</h1>
                        <span>Crawled with errors</span>
                    </div>
                </div>
            </div>
        </div>
    `,
});
