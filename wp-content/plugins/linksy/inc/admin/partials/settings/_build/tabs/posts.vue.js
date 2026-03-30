const POST_SETTINGS_DESCRIPTIONS = {
    suggestions_ignored_post: `When checked, this setting adds target="_blank" attribute to every internal link created by Linkly, thus making them open in a new tab. Only use this if it's necessary. You can change this setting for a single post on the post edit screen`,
    inbound_ignored_post: `When this is checked, the post will be removed from suggestions on the “add inbound links” page. To stop this post from getting links from or sending links to other pages instead, use the Pages Limit setting on the General settings tab.`,
    show_suggestions_used_phrases: `By default, Linkly will not suggest an anchor with a live link on any page. Whenever you want used anchors or keywords to be suggested for a single post, you can change this by checking this option. This feature works for a single post and not sitewide.`,
};

const PostForm = ({
    components: {
        LinksyForm,
        SettingCard,
    },
    props: {
        post: {type: Object, required: true},
        initialValues: {type: Array, default: []},
    },
    data() {
        return {
            settingsDescriptions: POST_SETTINGS_DESCRIPTIONS
        }
    },
    computed: {
        settings: {
            get() {
                const settings = {};

                this.initialValues.forEach(e => {
                    settings[e.key] = !!+e.value;
                });

                return settings;
            }
        }
    },
    methods: {
        validateSettings: function(values) {
            const errors = {};
            if (!values.token) {
                errors.token = "Token is required";
            }
            return errors;
        },

        saveSettings: async function(values, { setSubmitting }) {
            try {
                var params = urlSearchParams({
                    'action': 'linksy_settings_save_posts',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'post_id': this.post.id,
                    'meta_keys': JSON.stringify(Object.keys(values)),
                    'meta_values': JSON.stringify(Object.values(values)),
                });

                await axios.post(ajaxurl, params);

                const settings = Object.keys(values).map(e => ({
                    key: e,
                    value: values[e]? 1 : 0
                }));

                this.$emit('onSubmit', this.post, settings);

                this.$toast.success('Settings have been updated successfully');
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message || "failed to save setting");
            } finally {
                setSubmitting(false);
            }
        },
    },
    template: `
        <linksy-form class="ps-2" :validate="validateSettings" :initial-values="settings" :on-submit="saveSettings"
            v-slot="{
                values,
                errors,
                isSubmitting,
                handleChange,
                handleSubmit,
                setFieldValue,
            }"
        >
            <section>
                <div class="section-content">
                    <setting-card
                        name="Stop showing this post on Post Edit Page"
                        :description="settingsDescriptions.suggestions_ignored_post"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="suggestions_ignored_post"
                                v-model="values.linksy_suggestions_ignored_post"
                            />
                            <label for="suggestions_ignored_post"></label>
                        </div>
                    </setting-card>

                    <setting-card
                        name="Stop showing this post on Add Inbound Links Page"
                        :description="settingsDescriptions.inbound_ignored_post"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="inbound_ignored_post"
                                v-model="values.linksy_inbound_ignored_post"
                            />
                            <label for="inbound_ignored_post"></label>
                        </div>
                    </setting-card>


                    <setting-card
                        name="Resuggest used keywords"
                        :description="settingsDescriptions.show_suggestions_used_phrases"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="show_suggestions_used_phrases"
                                v-model="values.linksy_show_suggestions_used_phrases"
                            />
                            <label for="show_suggestions_used_phrases"></label>
                        </div>
                    </setting-card>
                </div>
            </section>

            <br />
            <div class="d-flex">
                <button :disabled="isSubmitting" class="btn-app ms-2" @click="handleSubmit">Save settings</button>
            </div>

        </linksy-form>
    `,
});

const Posts = ({
    name: 'Posts',
    components: {
        PostForm,
        LinksySearch,
    },
    props: {
        tab: Object,
    },
    data() {
        return {
            q: '',
            isLoading: false,
            searchedPosts: [],
            orphanedPosts: [],
            currentPost: null,
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
        handleSearch: async function(q) {
            try {
                this.q = q;
                this.isLoading = true;

                if (q && q.length > 1) {
                    const params = toQueryString({
                        action: 'linksy_settings_all_posts',
                        nonce: LINKSY_SECURE_TOKEN,
                        q
                    });
                    const {data: {data}} = await axios.get(ajaxurl+'?'+params);
                    this.searchedPosts = data;
                    this.currentPost = null;
                } else {
                    if (this.currentPost != null) {
                        this.currentPost = null;
                    }
                }

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },

        handlePostClicked: function(post) {
            this.currentPost = post;
        },

        handlePostSaved: async function(post, settings) {
            const postIndex = this.orphanedPosts.findIndex(e => e.post.id == post.id)

            if (postIndex !== -1) {
                this.orphanedPosts[postIndex]['settings'] = settings;
            } else {
                this.orphanedPosts.push({
                    post,
                    settings
                })
            }
        },

        loadPosts: async function() {
            try {
                this.isLoading = true;

                var params = urlSearchParams({
                    'action': 'linksy_settings_load_posts',
                    'nonce': LINKSY_SECURE_TOKEN,
                });
    
                const {data: {data}} = await axios.post(ajaxurl, params);

                this.orphanedPosts = data;
                if (data.length > 0) {
                    this.currentPost = this.orphanedPosts[0];
                }
            } catch (error) {
                
            } finally{
                this.isLoading = false;
            }
        }
    },
    template: `
            <div :id="tab.id" class="row">
                <div class="col-12 col-md-4 pt-2">
                    <linksy-search cancelable placeholder="Search any post" @on-change="handleSearch"></linksy-search>
                    <ul>
                        <li v-for="(post, index) in posts" @click="handlePostClicked(post)" :class="{active: currentPost == post}">
                            {{post?.post?.title}}
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-8">
                    <p v-if="posts.length < 1 && !isLoading" class="px-3">The post settings tab saves custom settings for individual posts and is helpful when you want a one-way limit for a post as against the two-way limit on the “Pages Limit” section of the General Settings tab. You can use the search box to search a post to apply the settings to or use the settings tab on Linkly Widget on the post edit screen.</p>
                    <post-form
                        v-if="currentPost"
                        :post="currentPost?.post"
                        :key="currentPost?.post?.id"
                        :initial-values="currentPost?.settings"
                        @on-submit="handlePostSaved">
                    </post-form>
                </div>
            </div>
        
    `,
    beforeMount() {
        this.loadPosts()
    }
});