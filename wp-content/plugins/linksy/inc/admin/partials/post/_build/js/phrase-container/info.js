const LinksyPhraseInfo = ({
    name: 'LinksyPhraseInfo',
    data() {
        return {
            inboundLinksCnt: null,
            outboundLinksCnt: null,
            externalLinksCnt: null,
            keywords: []
        }
    },
    computed: {
        linkInUrl: {
            get() {
                return LINKSY.admin_url+'?page=Linksy-inbound-links&post_id='+LINKSY_POST_ID;
            }
        }
    },
    template: `
        <div class="linksy-phrase linksy-phrase-info">
            <h4>Page Info.</h4>

            <div class="linksy-phrase-info-body">
                <table>
                    <tr>
                        <th style="text-align: left;"><i class="fa fa-arrow-down"></i></th>
                        <th><i class="fa fa-arrow-up"></i></th>
                        <th style="text-align: right;"><i class="fa fa-arrow-up-right-from-square"></i></th>
                    </tr>
                    <tr>
                        <td style="text-align: left;">{{inboundLinksCnt}}</td>
                        <td>{{outboundLinksCnt}}</td>
                        <td style="text-align: right;">{{externalLinksCnt}}</td>
                    </tr>
                </table>

                <div v-for="keyword in keywords">
                    {{keyword.phrase}}
                    <div class="score" :style="{backgroundColor: keyword.color}">{{keyword.score}}</div>
                </div>
            </div>

            <div class="linksy-phrase-actions">
                <a class="button apply" :href="linkInUrl" target="_blank">Add Inbound Link</a>
            </div>
        </div>
    `,
    async mounted() {
        try {
            var params = {
                'action': 'linksy_post_get_summary',
                'nonce': LINKSY_SECURE_TOKEN,
                'post_id': LINKSY_POST_ID
            };

            const {data: {data}} = await axios.get(ajaxurl+'?'+toQueryString(params));

            this.keywords = data.keywords.map(e => {
                return {
                    ...e,
                    color: scoreToColor(e.score),
                    score: Math.round((e.score + Number.EPSILON) * 100),
                }
            });
            this.inboundLinksCnt = data.links.inbound_links;
            this.outboundLinksCnt = data.links.outbound_links;
            this.externalLinksCnt = data.links.external_links;

        } catch (error) {
            console.log(error.response?.data?.data || error.message);
        }
    }
});