<div id="linksy-phrase-highlights"></div>

<script data-template="linksy-phrase-summary-template" type="text/x-custom-template">
    <div class="linksy-phrase-summary-container ${class}" style="position:absolute;">
        <div class="linksy-phrase-summary">
            <div class="linksy-phrase-summary-header">
                <h5>${title}</h5>
            </div>
            <div class="linksy-phrase-summary-footer">
                <div>
                    <button class="see-all">
                        <i class="fa fa-angles-down"></i>&nbsp;
                        <span>See more</span>
                    </button>
                    <button class="ignore">
                        <i class="fa fa-times-square"></i>&nbsp;
                        <span>Ignore</span>
                    </button>
                </div>

                <div class="linksy-phrase-summary-position">
                    ${position}
                </div>

                <div class="circle-wrap">
                    <div class="circle">
                        <div class="mask full" style="transform: ${rotation}">
                            <div class="fill" style="background: ${color}; transform: ${rotation};"></div>
                        </div>
                        <div class="mask half">
                            <div class="fill" style="background: ${color}; transform: ${rotation};"></div>
                        </div>
                        <div class="inside-circle" style="color:  ${color}"> ${percentile}% </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>