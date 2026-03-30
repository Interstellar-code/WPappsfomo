const LinksyDomains = ({
    name: 'LinksyDomains',
    props: {
        items: [Array, Object, null],
    },
    computed: {
        domains: {
            get() {
                return Object.keys(this.items).map(domain => this.transform(domain, this.items[domain])).filter(e => e.domain).sort((a, b) => b.links_cnt - a.links_cnt).slice(0, 5);
            }
        }
    },
    methods: {
        transform(domain, pages) {
            const posts = [];

            pages.forEach(page => {
                const index = posts.findIndex(e => e.id === page.post.ID)
                
                if (index === -1) {
                    posts.push({
                        id: page.post.ID,
                    });
                }
            });

            return {
                'domain': domain,
                'links_cnt': pages.length,
                'pages_cnt': posts.length,
            };
        },
    },
    template: `
        <div class="card shadow linksy-domains">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Most linked to domains</h2>
                    <a class="button btn-app" :href="to">See all</a>
                </div>

                <div class="linksy-domains-items">
                    <div v-for="item in domains" class="d-flex justify-content-between align-items-center border-bottom">
                        <span>{{item.domain}}</span>
                        <div class="d-flex">
                            <h5>Pages: <b>{{item.pages_cnt}}</b></h5>
                            <span>&nbsp;&nbsp;</span>
                            <h5>Links: <b>{{item.links_cnt}}</b></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            to: LINKSY.admin_url+'?page=Linksy-reports#domain-report'
        }
    },
});
