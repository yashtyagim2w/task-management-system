<?php
function card($title, $href, $isActive = false) {
    ob_start();
    ?>
    <a href="<?= $href ?>" 
       class="sidebar-card <?= $isActive ? 'tab-active' : '' ?>"
    >  
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 18px;">
                <?= $title ?>
            </h3>
        </div>
    </a>
    <?php
    return ob_get_clean();
}