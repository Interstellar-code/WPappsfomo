const KeywordSearch = ({
    name: 'KeywordSearch',
    components: {
        LinksySearch,
    },
    props: {
        tab: Object,
    },
    data() {
        return {
            mode: 'single',
            query: [],
            limit: 10,
            message: '',
            error: false,
            isSubmitting: false,
            results: []
        }
    },
    watch: {
        mode: function(val) {
            if (val == 'single' && this.results.length > 1) {
                this.results = [this.results[0]]
            }
        }
    },
    computed: {
        
    },
    methods: {
        async getItems() {
            this.results = []
            this.isSubmitting = true;

            try {
              const {data: {data}} = await axios.get(ajaxurl, {
                params: {
                    action: 'linksy_playground_search',
                    query: this.query.join(),
                    limit:  this.limit - 1,
                    nonce: LINKSY_SECURE_TOKEN,
                }
              });

              data.forEach(res => {
                this.results.push(
                    res.documents.filter((e) => e.score && e.score > 0 && e.post_title).map(e => ({
                        ...e,
                        color: scoreToColor(e.score)
                    }))
                )
              });

              this.error = false;
              this.isSubmitting = false;
            } catch (error) {
                this.error = true;
                this.isSubmitting = false;
                this.message = error.response?.data?.data || error.message;
            }
        }
    },
    template: `
        <div :id="tab.id" :class="[mode]">
            <div class="controller shadow">
                <div style="display: flex; justofy-content: space-between; margin-bottom: 30px;">
                    <linksy-search
                        placeholder="Type to search"
                        @onChange="(q) => query[0] = q"
                    >
                    </linksy-search>

                    <linksy-search
                        v-if="mode == 'versus'"
                        placeholder="Type to search"
                        style="margin-left: 50px;"
                        @onChange="(q) => query[1] = q"
                    >
                    </linksy-search>
                </div>

                <div class="controller-actions">
                    <div>
                        <span>Result</span>
                        <select v-model="limit">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div style="display: flex;">
                        <div style="margin-right: 20px;">
                            <input type="radio" name="search-mode" value="single" v-model="mode" />
                            <span>&nbsp;Single</span>
                        </div>
                        <div>
                            <input type="radio" name="search-mode" value="versus" v-model="mode" />
                            <span>&nbsp;Versus</span>
                        </div>
                    </div>
                    <div>
                        <button
                            type="button"
                            @click="getItems"
                            :disabled="isSubmitting || query.length < 1"
                        >
                            <i class="fa fa-check"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="results">
                <div v-for="result in results">
                    <ul>
                        <li v-for="post in result" :key="post.ID">
                            {{post.post_title}}
                            <span
                                class="score"
                                :style="{backgroundColor: post.color}"
                            >
                                {{Math.round((post.score + Number.EPSILON) * 100)}}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    `,
});