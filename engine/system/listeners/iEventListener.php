<?php

namespace system\listeners {

    interface iEventListener {
        
        const INIT_CONTROLLER = 1;
        
        public function onEvent($event);
    }

}
