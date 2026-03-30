const GENERAL_SETTINGS_DESCRIPTIONS = {
    open_internal_link_in_new_tab: `When checked, this setting adds target="_blank" attribute to every internal link created by Linkly, thus making them open in a new tab. Only use this if it's necessary. You can change this setting for a single post on the post edit screen`,
    no_of_type_to_skip: `Number of Words/Sentences/Paragraphs to Skip: When this option is set, keyword suggestions will be skipped at the beginning of every post up to the set limit. The recommended "Words" option works best for 'excerpt word count' when you don't want links to show on archive pages.`,
    ignore_image_urls: `By default, image Urls are excluded from the links report page. You can uncheck the setting when you want image links to be included in the report. We recommend that you leave this checked.`,
    ignore_post_older_than: `Control how long you want a post to get onsite links to them by setting a date limit. When the set date is reached, the post will no longer be suggested on the post edit screen. This setting does not apply to manual linking to the restricted pages. This can be helpful when you want newer posts to get more links but can reduce the quality of the link suggestions you get. We don’t recommend this.  `,
    suggestions_ignored_posts: `When you add a post to this list, they are removed from suggestions on both the “post edit screen” and the “add inbound links” page. Use this when you want to totally limit a post from being considered for link suggestions. If you only want a partial limit, use the option on the “Post settings” tab`,
    suggestions_ignored_phrases: ``,
    disable_two_way_linking: `This setting prevents two posts from having links to each other: If “post A” has a link to “post B”, the system will exclude any link suggestions from “post B” to “post A”. When this is set, the other post will not appear on the list of suggestions on the “post edit screen” and the “add inbound link” page.`,
    disable_link_resuggestion: `This setting prevents resuggestion of posts that already have links present in a post. If "post B" has a link inside "post A", the system will exclude suggestions to "post B". When this is set, the other post will no longer appear on the “post edit screen” as suggestions, and also will not appear on the “add inbound links” page.`,
    update_post_modified_date: `By default, adding a link from one post to another is like editing it in real time, even if this is not done on the edit screen, thereby updating the post's modified date. This setting helps keep track of changes made to the post and can help keep records. When you don't care about such records, you can uncheck this.`,
    add_rel_to_external_links: `The "rel=noopener" tag blocks every referral information from your page to the page you are linking to, for security and performance. We recommend checking this when you set external links to open in a new tab.`,
    max_inbound_links_per_post: `This sets the total number of links a post can get from other pages of your site - onsite inbound links. Once a post reaches the set number, it won't be suggested on the post edit screen.`,
    max_outbound_links_per_post: `This sets the total number of links you want a post to send out to other pages of your site - onsite outbound links. Once a post reaches the set number, it won't be shown as a source on the "add inbound links" page. Additionally, they won't be shown on the auto-link page. This setting doesn't take external links into consideration.`,
    suggestions_ignored_categories: `Set a limit to what categories you want Linksy to use for suggestions. Posts from the categories you add here will not get suggestions from and to other posts.`,
    suggestions_post_types: `Choose the Post types to sync for suggestions. Whenever you make any change to this setting, endeavour to use the "Resync" button on the dashboard page to add the content of the new post types.`,
    open_external_link_in_new_tab: `When checked, this setting adds target="_blank" attribute to every external link created by Linkly, thus making them open in a new tab. We recommend checking the rel="noopener" option when you have this checked.`,
    show_suggestions_ignored_posts_in_reports: `This option is important when you stop a link from getting links or sending out links but you still want to have their link reports shown on the reports page. We recommend this to be checked`,
    add_destination_post_title_to_links: `The title attributes function grabs the title of the page you are linking to and displays this when a user hovers on the link. We do not recommend this.`,
};

const General = ({
    name: 'General',
    components: {
        SettingCard,
        LinksyForm,
        LinksyDropdown,
    },
    props: {
        tab: Object,
    },
    data() {
        return {
            statuses: [],
            types: LINKSY_POST_TYPES,
            categories: [],
            settings: {
                type_to_skip: 'words',
                no_of_type_to_skip: 0,
                ignore_image_urls: true,
                ignore_post_older_than: null,
                suggestions_ignored_posts: [],
                suggestions_ignored_phrases: [],
                disable_two_way_linking: false,
                disable_link_resuggestion: true,
                update_post_modified_date: true,
                add_rel_to_external_links: false,
                max_inbound_links_per_post: -1,
                max_outbound_links_per_post: -1,
                suggestions_ignored_categories: [],
                suggestions_post_types: ['post', 'page'],
                open_internal_link_in_new_tab: false,
                open_external_link_in_new_tab: false,
                show_suggestions_ignored_posts_in_reports: true,
                add_destination_post_title_to_links: false,
            },
            settingsDescriptions: GENERAL_SETTINGS_DESCRIPTIONS
        }
    },
    methods: {
        validateSettings: function(values) {
            const errors = {};
            console.table(values)
            return errors;
        },

        saveSettings: async function(values, options) {
            this.$emit('onSubmit', values, options);
        }
    },
    template: `
        <linksy-form :id="tab.id" :validate="validateSettings" :initial-values="settings" :on-submit="saveSettings"
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
                <div class="section-title">Link Tags</div>

                <div class="section-content">
                    <setting-card
                        name="Open internal link in a new tab"
                        :description="settingsDescriptions.open_internal_link_in_new_tab"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="open_internal_link_in_new_tab"
                                v-model="values.open_internal_link_in_new_tab"
                            />
                            <label for="open_internal_link_in_new_tab"></label>
                        </div>
                    </setting-card>

                    <setting-card
                        name="Add Destination Post Title to Links"
                        :description="settingsDescriptions.add_destination_post_title_to_links"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="add_destination_post_title_to_links"
                                v-model="values.add_destination_post_title_to_links"
                            />
                            <label for="add_destination_post_title_to_links"></label>
                        </div>
                    </setting-card>
                </div>
            </section>

            <br />

            <section>
                <div class="section-title">Link Limits</div>
            
                <div class="section-content">
                    <setting-card
                        :description="settingsDescriptions.no_of_type_to_skip"
                    >
                        <template v-slot:name>
                            <div>
                                No. of &nbsp;
                                <select name="type_to_skip" @change="handleChange" style="width: 80px;">
                                    <option v-for="i in ['words', 'sentences', 'paragraphs']" :key="i" :selected="values.type_to_skip == i" :value="i">{{i}}</option>
                                </select>
                                &nbsp; to skip
                            </div>
                        </template>

                        <select name="no_of_type_to_skip" @change="handleChange">
                            <option value="0">0</option>
                            <option v-for="i in 100" :key="i" :selected="i == values.no_of_type_to_skip" :value="i">{{i}}</option>
                        </select>
                    </setting-card>

                    <setting-card
                        name="Max Outbound Links Per Post"
                        :description="settingsDescriptions.max_outbound_links_per_post"
                    >
                        <select name="max_outbound_links_per_post" @change="handleChange">
                            <option value="null">No Limit</option>
                            <option v-for="i in 100" :key="i" :selected="i == values.max_outbound_links_per_post" :value="i">{{i}}</option>
                        </select>
                    </setting-card>

                    <setting-card
                        name="Max Inbound Links Per Post"
                        :description="settingsDescriptions.max_inbound_links_per_post"
                    >
                        <select name="max_inbound_links_per_post" @change="handleChange">
                            <option value="-1">No Limit</option>
                            <option v-for="i in 100" :key="i" :selected="i == values.max_inbound_links_per_post" :value="i">{{i}}</option>
                        </select>
                    </setting-card>

                    <setting-card
                        name="Don't Suggest Links to Posts Older Than"
                        :description="settingsDescriptions.ignore_post_older_than"
                    >
                        <select name="ignore_post_older_than" @change="handleChange">
                            <option value="-1">No Limit</option>
                            <option v-for="i in 100" :key="i" :selected="i == values.ignore_post_older_than" :value="i">{{i}} months</option>
                        </select>
                    </setting-card>

                    <setting-card
                        name="Prevent Two-Way Linking"
                        :description="settingsDescriptions.disable_two_way_linking"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="disable_two_way_linking"
                                v-model="values.disable_two_way_linking"
                            />
                            <label for="disable_two_way_linking"></label>
                        </div>
                    </setting-card>

                    <setting-card
                        name="Don't Resuggest Links"
                        :description="settingsDescriptions.disable_link_resuggestion"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="disable_link_resuggestion"
                                v-model="values.disable_link_resuggestion"
                            />
                            <label for="disable_link_resuggestion"></label>
                        </div>
                    </setting-card>

                    <setting-card
                        name="Ignore Image URLs"
                        :description="settingsDescriptions.ignore_image_urls"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="ignore_image_urls"
                                v-model="values.ignore_image_urls"
                            />
                            <label for="ignore_image_urls"></label>
                        </div>
                    </setting-card>

                    <setting-card
                        name='Update "Post Modified" Date when Links Created'
                        :description="settingsDescriptions.update_post_modified_date"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="update_post_modified_date"
                                v-model="values.update_post_modified_date"
                            />
                            <label for="update_post_modified_date"></label>
                        </div>
                    </setting-card>
                </div>
            </section>
            
            <br />

            <section>
                <div class="section-title">Categories and Post Types</div>
            
                <div class="section-content">
                    <setting-card
                        name="Categories to be Ignored for Suggestions"
                        :description="settingsDescriptions.suggestions_ignored_categories"
                    >
                        <linksy-dropdown
                            multiple
                            :items="categories"
                            :initialValue="values.suggestions_ignored_categories"
                            @selected="e => setFieldValue('suggestions_ignored_categories', e)"
                            style="border: 1px solid #8c8f94;"
                        >
                            <span style="font-size: 16px; white-space: nowrap;">{{values.suggestions_ignored_categories.length}} Added</span>
                        </linksy-dropdown>
                    </setting-card>

                    <setting-card
                        name="Post Types to Sync"
                        :description="settingsDescriptions.suggestions_post_types"
                    >
                        <linksy-dropdown
                            multiple
                            :items="types"
                            :initialValue="values.suggestions_post_types"
                            @selected="e => setFieldValue('suggestions_post_types', e)"
                            style="border: 1px solid #8c8f94;"
                        >
                            <span style="font-size: 16px; white-space: nowrap;">{{values.suggestions_post_types.length}} Added</span>
                        </linksy-dropdown>
                    </setting-card>
                </div>
            </section>

            <br />

            <section>
                <div class="section-title">Pages Limit</div>
            
                <div class="section-content">
                    <setting-card
                        name="Posts to be ignored for suggestions"
                        :description="settingsDescriptions.suggestions_ignored_posts"
                    >
                        <linksy-dropdown
                            multiple
                            creatable
                            :items="values.suggestions_ignored_posts"
                            :initialValue="values.suggestions_ignored_posts"
                            @selected="e => setFieldValue('suggestions_ignored_posts', e)"
                            style="border: 1px solid #8c8f94;"
                        >
                            <span style="font-size: 16px; white-space: nowrap;">{{values.suggestions_ignored_posts.length}} Added</span>
                        </linksy-dropdown>
                    </setting-card>

                    <setting-card
                        name="Show ignored posts in reports"
                        :description="settingsDescriptions.show_suggestions_ignored_posts_in_reports"
                    >
                        <div class="switch-control">
                            <input
                                type="checkbox"
                                id="show_suggestions_ignored_posts_in_reports"
                                v-model="values.show_suggestions_ignored_posts_in_reports"
                            />
                            <label for="show_suggestions_ignored_posts_in_reports"></label>
                        </div>
                    </setting-card>
                </div>
            </section>
            
            <br />
            <button :disabled="isSubmitting" class="btn-app" @click="handleSubmit">Save settings</button>
        </linksy-form>
    `,
    beforeMount() {
        const boolTypes = [
            'ignore_image_urls',
            'disable_two_way_linking',
            'disable_link_resuggestion',
            'update_post_modified_date',
            'add_rel_to_external_links',
            'open_internal_link_in_new_tab',
            'open_external_link_in_new_tab',
            'add_destination_post_title_to_links',
            'show_suggestions_ignored_posts_in_reports'
        ];

        for (setting in this.settings) {
            if (setting in LINKSY_SETTINGS) {
                this.settings[setting] = boolTypes.includes(setting)? !!+LINKSY_SETTINGS[setting] : (LINKSY_SETTINGS[setting] || this.settings[setting]);
            }
        }

        this.categories = Object.values(LINKSY_POST_CATEGORIES).map(e => ({label: e.name, value: e.id}));
    }
});