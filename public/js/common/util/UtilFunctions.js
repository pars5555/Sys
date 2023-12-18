SysUtil = {
    validateEmail: function (email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    },
    russianLettersArray: function () {
        return [
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
            'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
        ];
    },
    armenianLettersArray: function () {
        return [
            'ա', 'բ', 'գ', 'դ', 'ե', 'զ', 'է', 'ը', 'թ', 'ժ', 'ի', 'լ', 'խ',
            'ծ', 'կ', 'հ', 'ձ', 'ղ', 'ճ', 'մ', 'յ', 'ն', 'շ', 'ո', 'չ', 'պ',
            'ջ', 'ռ', 'ս', 'վ', 'տ', 'ր', 'ց', 'ո', 'ւ', 'փ', 'ք', 'և', 'օ', 'ֆ',
            'Ա', 'Բ', 'Գ', 'Դ', 'Ե', 'Զ', 'Է', 'Ը', 'Թ', 'Ժ', 'Ի', 'Լ', 'Խ',
            'Ծ', 'Կ', 'Հ', 'Ձ', 'Ղ', 'Ճ', 'Մ', 'Յ', 'Ն', 'Շ', 'Ո', 'Չ', 'Պ',
            'Ջ', 'Ռ', 'Ս', 'Վ', 'Տ', 'Ր', 'Ց', 'Ո', 'Փ', 'Ք', 'Օ', 'Ֆ'
        ];
    },
    englishLettersArray: function () {
        return [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];
    },
    checkArmenianCellPhone: function (phone) {
        phone = phone.trim();
        var cellPhoneCode = "";
        if (phone[0] === '0')
        {
            if (phone.length !== 9)
            {
                return false;
            }
            cellPhoneCode = phone[1] + phone[2];
        } else
        {
            if (phone.length !== 8)
            {
                return false;
            }
            cellPhoneCode = phone[0] + phone[1];
        }
        return (['91', '99', '96', '43', '55', '95', '41', '44', '93', '94', '77', '98', '49'].indexOf(cellPhoneCode) !== -1);

    },
    checkArmenianRussianEnglishName: function (name) {
        var en = this.englishLettersArray();
        var ru = this.russianLettersArray();
        var am = this.armenianLettersArray();
        for (var i = 0, len = name.length; i < len; i++) {
            if (en.indexOf(name[0]) === -1 && ru.indexOf(name[0]) === -1 && am.indexOf(name[0]) === -1)
            {
                return false;
            }
        }
        return true;
    }
};