(function ($) {
  $.fn.formValidator = function (options) {
    var defaults = {
      showError: validator.showError,
      hideError: validator.hideError
    };
    var options = $.extend(defaults, options);
    var passwordFieald = false;
    var emailReFieald = false;
    var formValidateStatus = true;
    $(this).find(":input").each(function (key, item) {
      var status = true;
      if($(item).attr("data-validate")){
        var validateType = $(item).attr("data-validate");
        switch (validateType) {
          case "number":
            var status = validator.validateNumber($(item).val());
            break;
          case "phone":
            var status = validator.validatePhone($(item).val(), 0);
            break;
          case "float-number":
            var status = validator.validateFloatNumber($(item).val());
            break;
          case "float-number-space":
            var status = validator.validateFloatNumberSpace($(item).val());
            break;
          case "float-number-new":
            var status = validator.validateFloatNumberNew($(item).val());
            break;
          case "string":
            var status = validator.validateString($(item).val(), 1, false);
            break;
          case "text":
            var status = validator.validateText($(item).val());
            break;
          case "email":
            var status = validator.validateEmail($(item).val());
            if(emailReFieald && status){
              status = validator.validateEmail($(item).val(), emailReFieald.val());
            }
            emailReFieald = $(item);
            break;
          case "username":
            var status = validator.validateString($(item).val(), 2, true);
            break;
          case "username-email":
            var status = validator.validateString($(item).val(), 2, true, true);
            break;
          case "password":
            var status = validator.validateString($(item).val(), 0, false);

            // if (passwordFieald && status == true) {
            //   status = validator.validatePasswords($(item).val(), passwordFieald.val());
            // }
            // passwordFieald = $(item);
            break;
          case "space":
            var status = validator.validateSpace($(item).val());
            break;
          case "mobile-number":
            var status = validator.validateMobileNumber($(item).val());
            break;
          case "policy":
            var status = validator.validatePolicy(item);
            break;
          case "cc_expiration_date":
            var status = validator.validateCCExpirationDate($(item).val());
            break;
          case "ccv":
            var status = validator.validateCCV($(item).val());
            break;
        }
        if(status !== true){
          formValidateStatus = false;
          options.showError(item, status);
        } else{
          options.hideError(item);
        }
      }
    });
    return formValidateStatus;
  };

  $.fn.formValidatorByJson = function (jsonArr) {
    var passwordFieald = false;
    var formValidateStatus = true;
    $(this).find(":input").each(function (key, item) {
      if($(item).attr("validate")){
        var validateType = $(item).attr("validate");
        validator.hideError(item);
        if(jsonArr[validateType]){
          formValidateStatus = false;
          validator.showError(item, jsonArr[validateType]);
        }
      }
    });
    return formValidateStatus;
  };
  var validator = {

    validateEmail: function (str, str1) {
      str = str.trim();
      var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;

      if(!filter.test(str)){
        return "Please enter valid email";
      }

      if(str1){
        var str1 = str1.trim();
        if(str != str1){
          return "These emails don't match. Try again?";
        }
      }

      return true;
    },
    validateNumber: function (str) {
      var strTrim = str.trim();
      var filter = /^[0-9]*$/;

      if(!strTrim){
        return "You can't leave this empty.";
      }

      if(!filter.test(str)){
        return "Please use only numbers.";
      }

      return true;
    },
    validateFloatNumber: function (str) {
      var str = str.trim();
      var filter = /^-?\d*(\.\d+)?$/;
      if(!filter.test(str)){
        return "Please use only numbers.";
      }
      if(!str){
        return "You can't leave this empty.";
      }
      return true;
    },
    validateFloatNumberNew: function (str) {
      str = str.trim();
      var filter = /^[+-]?\d+(\.\d+)?$/;

      if(!filter.test(str)){
        return "Please use only numbers.";
      }

      if(!str){
        return "You can't leave this empty.";
      }

      return true;
    },
    validateSpace: function (str) {
      if(str !== "" && !str.trim()){
        return "Please don't use space.";
      }

      return true;
    },
    validateFloatNumberSpace: function (str) {
      str = str.trim();
      var filter = /^[+-]?\d+(\.\d+)?$/;

      if(str === ""){
        return true;
      } else if(!filter.test(str)){
        return "Please use only numbers.";
      }

      return true;
    },
    validateMobileNumber: function (str) {
      var str = str.trim();
      var filter = /^[0-9\+\.\-]*$/;
      if(!filter.test(str)){
        return "Please use only numbers.";
      }
      if(!str){
        return "You can't leave this empty.";
      }
      var str1 = str.replace(/\-/g, "");
      var str2 = str1.replace(/\./g, "");
      if(str2.length != 10 && str2.length != 11){
        return "invalid phone number";
      }
      return true;
    },
    validatePhone: function (str, len) {
      if(!str){
        return "You can't leave this empty.";
      }
      
      if(str.length<8){
        return "Short number";
      }
      var filter = /^[0-9 +()]+$/;
      if(!filter.test(str)){
        return "Please enter valid Phone";
      }

      if(len){
        if(str.length < len){
          return "Please do not use more when " + len + " characters.";
        }
      }

      return true;
    },
    validateString: function (str, len, allowChars, email) {
      var str = str.trim();
      if(!str){
        return "You can't leave this empty.";
      }
      if(len){
        if(str.length < len || str.length > 60){
          return "Please use between " + len + " and 60 characters.";
        }
      }
      if(allowChars){
        var filter = /^[A-Za-z0-9\_\-\.\s]*$/;
        if(email){
          filter = /^[A-Za-z0-9\_\-\.\@]*$/;
        }
        if(!filter.test(str)){
          return "Please use only letters (a-z), numbers, and periods.";
        }
      }
      return true;
    },
    validateText: function (str) {
      var str = str.trim();
      if(!str){
        return "You can't leave this empty.";
      }
      
      return true;
    },
    validateCCExpirationDate: function (str) {
      var str = str.trim();
      if(!str){
        return "You can't leave this empty.";
      }
      var filter = /^\d{2}\/{0,1}\d{2}$/;
      if(!filter.test(str)){
        return "Please use mm/yy format for date";
      }

      return true;
    },
    validateCCV: function (str) {
      var str = str.trim();
      if(!str){
        return "Please enter your card's CCV";
      }
      var filter = /^\d{3,4}$/;
      if(!filter.test(str)){
        return "Please enter correct CCV";
      }

      return true;
    },
    validatePasswords: function (str, str1) {
      var str = str.trim();
      var str1 = str1.trim();
      filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
      if(str !== str1){
        return "You passwords don't match. Can you please try again?";
      }
      return true;
    },
    validatePolicy: function (elem) {
      if(!$(elem).is(':checked')){
        return "In order to use our services, you must agree to our Terms of Use and Privacy Policy.";
      }
      return true;
    },
    showError: function (elem, msg) {
      // $(elem).parent().find('.validate_msg').remove();
      // $(elem).parent().append($("<div class='validate_msg'>" + msg + "</div>"));
      $(elem).addClass("validation_border");
    },
    hideError: function (elem) {
      // $(elem).parent().find(".validate_msg").remove();
      $(elem).removeClass("validation_border");
    }
  };
})($);

(function ($) {
  return $.fn.serializeObject = function (trim) {
    var json,
      patterns,
      push_counters,
      _this = this;
    json = {};
    push_counters = {};
    patterns = {
      validate: /^[a-zA-Z][a-zA-Z0-9._]*(?:\[(?:\d*|[a-zA-Z0-9._]+)\])*$/,
      key: /[a-zA-Z0-9._]+|(?=\[\])/g,
      push: /^$/,
      fixed: /^\d+$/,
      named: /^[a-zA-Z0-9._]+$/
    };
    this.build = function (base, key, value) {

      base[key] = value;
      return base;
    };
    this.push_counter = function (key) {
      if(push_counters[key] === void 0){
        push_counters[key] = 0;
      }
      return push_counters[key]++;
    };
    $.each($(this).serializeArray(), function (i, elem) {
      var k,
        keys,
        merge,
        re,
        reverse_key;
      if(!patterns.validate.test(elem.name)){
        return;
      }
      keys = elem.name.match(patterns.key);

      if(trim){
        merge = elem.value.trim();

      } else{
        merge = elem.value;
      }
      reverse_key = elem.name;
      while (( k = keys.pop()) !== void 0){

        if(patterns.push.test(k)){
          re = new RegExp("\\[" + k + "\\]$");
          reverse_key = reverse_key.replace(re, '');
          merge = _this.build({}, _this.push_counter(reverse_key), merge);
        } else if(patterns.fixed.test(k)){
          merge = _this.build({}, k, merge);
        } else if(patterns.named.test(k)){
          merge = _this.build({}, k, merge);
        }
      }

      return json = $.extend(true, json, merge);
    });
    return json;
  };
})($);

(function ($) {
  return $.fn.serializeObjectNew = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
      if(o[this.name] !== undefined){
        if(!o[this.name].push){
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || '');
      } else{
        o[this.name] = this.value || '';
      }
    });
    return o;
  };
})($);