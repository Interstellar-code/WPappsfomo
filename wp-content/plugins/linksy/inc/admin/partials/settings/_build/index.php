<div id="settings-app" v-cloak>
  <linksy-tab-bar persist title="Linksy Settings" :tab="tab" :tabs="tabs" @on-select="handleTabSelect" @on-tab-mounted="handleTabMounted"></linksy-tab-bar>
  <linksy-tab class="container" :tab="tab" :tabs="tabs" v-slot="{currentTab}">
    <general v-if="currentTab.id == 'general'" :tab="currentTab" @on-submit="saveSettings"></general>
    <posts v-if="currentTab.id == 'posts'" :tab="currentTab" @on-submit="saveSettings"></posts>
    <licensing v-if="currentTab.id == 'licensing'" :tab="currentTab" @on-submit="saveSettings"></licensing>
  </linksy-tab>
</div>