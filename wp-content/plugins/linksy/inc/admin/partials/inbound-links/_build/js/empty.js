Vue.createApp({
    components: {
        LinksySearch,
    },
    data() {
      return {
        q: '',
        isLoading: false,
        searchedPosts: [],
        orphanedPosts: [],
        linksy_admin_url: LINKSY.admin_url,
      }
    },
    computed: {
        posts: {
            get() {
               if (this.q && this.q.length > 2) {
                return this.searchedPosts;
               }
               return this.orphanedPosts;
            }
        },
    },
    methods: {
        async handleSearch(q) {
            try {
                this.q = q;
                this.isLoading = true;

                if (q && q.length > 1) {
                    const params = toQueryString({
                        action: 'linksy_inbound_links_get_posts',
                        nonce: LINKSY_SECURE_TOKEN,
                        q
                    });
                    const {data: {data}} = await axios.get(ajaxurl+'?'+params);
                    this.searchedPosts = data;
                }

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
        async getOrphanedPosts() {
            try {
                this.isLoading = true;
                const params = toQueryString({
                    action: 'linksy_inbound_links_get_orphans',
                    nonce: LINKSY_SECURE_TOKEN,
                });
                const {data: {data}} = await axios.get(ajaxurl+'?'+params);
                this.orphanedPosts = data;
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
    },
    mounted() {
        this.getOrphanedPosts();
    }
}).mount('#internal-links-empty-app');