<div class="container with-content" id="playground-app" v-cloak>
    <h1 class="container-title">Playground</h1>
    <linksy-tab-bar :tab="tab" :tabs="tabs" @on-select="handleTabSelect" v-slot="{tab, active}">
        <input type="radio" :checked="active" />
        <span>{{tab.title}}</span>
    </linksy-tab-bar>

    <div>
        <div class="tab-content keyword-search" v-if="selectedTab && selectedTab.id == 'keyword-search'">
            <keyword-search :tab="selectedTab"></keyword-search>
        </div>
    </div>
</div>