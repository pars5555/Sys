Sys = {
    controllers: [],
    host: window.location.hostname,
    drawModes: {REPLACE: 0, APPEND: 1, PREPEND: 2, BEFORE: 3, AFTER: 4, REPLACE_INNER: 5},
    ready: function (controllerArray)
    {
        for (var i = 0; i < controllerArray.length; i++) {
            var controllerInstance = this.findController(controllerArray[i].name);
            if (controllerInstance !== null)
            {
                controllerInstance.properties = controllerArray[i].properties;
                controllerInstance.init(controllerArray[i].params);
            }
        }
    },
    controller: function (controllerName, controllerInstance) {       
        if (typeof controllerInstance === 'undefined')
        {
            return this.findController(controllerName);
        }
        var controllerIndex = this.findControllerIndex(controllerName);
        if (controllerIndex !== -1) {
            Sys.controllers.splice(controllerIndex, 1);
        }
        Sys.controllers.push({name: controllerName, instance: controllerInstance});
    },
    findControllerIndex: function (controllerName) {
        for (var i = 0; i < Sys.controllers.length; i++)
        {
            if (Sys.controllers[i].name === controllerName)
            {
                return i;
            }
        }
        return -1;
    },
    findController: function (controllerName) {
        for (var i = 0; i < Sys.controllers.length; i++)
        {
            if (Sys.controllers[i].name === controllerName)
            {
                return Sys.controllers[i].instance;
            }
        }
        return null;
    }
   
};


