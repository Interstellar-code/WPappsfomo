<div class="linksy-phrase-container" style="margin-left: -5px; margin-right: -5px;" v-cloak>
    <div class="loader" v-if="isLoading">
        <span>
            <i class="fa fa-spin fa-spinner"></i>
        </span>
    </div>

    <div class="linksy-phrase-container-header d-flex justify-content-between align-items-center">
        <div class="linksy-phrase-container-header-controler">
            <div v-if="isHome">
                <div><small>&nbsp;&nbsp;{{selectedPosts.length}}</small>/{{items.length}}</div>
            </div>
            <div v-else>
                <button class="active btn-clear" @click="handleTabSelect(0)">
                    <i class="fa fa-arrow-left-long"></i>
                </button>
            </div>
        </div>
        <linksy-tab-bar class="linksy-phrase-container-header-meta" :tab="currentMode" :tabs="modes" @on-select="handleTabSelect">
            <template v-slot="{active, tab, index}">
                <button type="button" :class="{active, 'd-none': index == 0}">
                    <i class="fa" :class="tab.icon"></i>
                </button>
            </template>
        </linksy-tab-bar>
    </div>
    <div v-if="message" class="linksy-phrase-container-alert">
        {{message}}
    </div>
    <div class="linksy-phrase-container-body">
        <linksy-tab :tab="currentMode" :tabs="modes" v-slot="{currentTab, currentTabIndex}">
            <div v-if="isHome" class="linksy-phrase-home">
                <linksy-phrase-home
                    :items="items"
                    :key="modes[currentTabIndex]['key']"
                    @on-phase-ignore="handlePhraseIgnore"
                    @on-phase-item-add="handlePhraseItemAdd"
                    @on-phase-item-ignore="handlePhraseItemIgnore"
                    @on-phrasing="handlePhrasesChanged"
                >
                </linksy-phrase-home>
            </div>
            <linksy-phrase-filters
                v-if="currentTabIndex == 1"
                :types="types"
                :filters="filters"
                :categories="categories"
                @on-filter="handleFiltersChange"
            >
            </linksy-phrase-filters>
            <linksy-phrase-info
                v-if="currentTabIndex == 2"
            >
            </linksy-phrase-info>
            <linksy-phrase-settings
                v-if="currentTabIndex == 3"
                :settings="settings"
                @on-setting="handleSettingsChange"
            >
            </linksy-phrase-settings>
        </linksy-tab>
    </div>
</div>