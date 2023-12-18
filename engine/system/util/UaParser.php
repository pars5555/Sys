<?php

namespace system\util {

    class UaParser {

        public static function parse($userAgent) {
            $ret = [];
            $ua_parser_data = json_decode(json_encode(\UAParser\Parser::create()->parse($userAgent)), true);
            if (isset($ua_parser_data) && isset($ua_parser_data['ua']) && isset($ua_parser_data['ua']['family']) && stripos('chrome', $ua_parser_data['ua']['family']) !== false) {
                $ua_parser_data['ua']['family'] = 'Google Chrome';
            }
            $matches = [];
            if (preg_match('/Mozilla\/.*\/(\d+.\d+.\d+.(\d+))/', $userAgent, $matches)) {
                $ua_parser_data['ua']['full_version'] = $matches[1];
                $ua_parser_data['ua']['build'] = $matches[2];
            }
            $ret['ua_parser_data'] = $ua_parser_data;
            $ret['uad_device_type'] = \system\util\UaParser::getDeviceTypeFromUserAgent($userAgent);
            $uaVersionDetailsMatches = [];
            if (preg_match('/\/(\d{2,3})\.(\d{1,2})\.(\d{1,4})\.(\d{1,3})/', $userAgent, $uaVersionDetailsMatches)) {
                $ret['uad_ua_version_deails'] = array_merge([trim($uaVersionDetailsMatches[0], '/')], array_slice($uaVersionDetailsMatches, 1));
            }
            

            return array_merge($ret, self::getNavigatorUadPlatformBitnessArchABrandModel($userAgent, $ua_parser_data));
        }

        private static function detectNavigatorUADPlatform($ua, $build_for_device_type = null) {
            $deviceTypeIndex = 0;
            switch ($build_for_device_type) {
                case 'mulogin':
                    $deviceTypeIndex = 1;
                    break;
                case 'gologin':
                    $deviceTypeIndex = 2;
                    break;
            }
            if (stripos($ua, '(Mac')) {
                return ['Mac OS', 'MacOS', 'mac'][$deviceTypeIndex];
            } else if (stripos($ua, 'iPhone') || stripos($ua, 'iPad') || stripos($ua, 'iPod')) {
                return ['iOS', 'IOS', 'ios'][$deviceTypeIndex];
            } else if (stripos($ua, '(Win')) {
                return ['Windows', 'Windows', 'win'][$deviceTypeIndex];
            } else if (stripos($ua, 'Android')) {
                return ['Android', 'Android', 'android'][$deviceTypeIndex];
            } else if (stripos($ua, 'linux')) {
                return ['Linux', 'Linux', 'lin'][$deviceTypeIndex];
            }
            return ['Linux', 'Linux', 'lin'][$deviceTypeIndex];
        }

        private static function getNavigatorUadPlatformBitnessArchABrandModel($userAgent, $ua_parser_data) {
            $ret = [];
            $ret['uad_platform'] = self::detectNavigatorUADPlatform($userAgent);
            $ret['mulogin_os_type'] = self::detectNavigatorUADPlatform($userAgent, 'mulogin');
            $ret['gologin_os_type'] = self::detectNavigatorUADPlatform($userAgent, 'gologin');
            $ret['speech_synthesis_voices'] = self::calculateSpeechVoiceList($ret['uad_platform']);
            if (stripos($userAgent, 'Windows') !== false) {
                $ret['platform'] = 'Win32';
                $ret['uad_bitness'] = '64';
                $ret['uad_architecture'] = 'x86';
                $ret['uad_not_abrand_version'] = '99';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'iPhone') !== false) {
                $ret['platform'] = 'iPhone';
                $ret['uad_bitness'] = '';
                $ret['uad_architecture'] = '';
                $ret['uad_not_abrand_version'] = '';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'iPad') !== false) {
                $ret['platform'] = 'iPad';
                $ret['uad_bitness'] = '';
                $ret['uad_architecture'] = '';
                $ret['uad_not_abrand_version'] = '';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'iPod touch') !== false) {
                $ret['platform'] = 'iPod touch';
                $ret['uad_bitness'] = '';
                $ret['uad_architecture'] = '';
                $ret['uad_not_abrand_version'] = '';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'iPod') !== false) {
                $ret['platform'] = 'iPod';
                $ret['uad_bitness'] = '';
                $ret['uad_architecture'] = '';
                $ret['uad_not_abrand_version'] = '';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'Linux x86_64') !== false || stripos($userAgent, 'X11') !== false) {
                $ret['platform'] = 'Linux x86_64';
                $ret['uad_bitness'] = '64';
                $ret['uad_architecture'] = 'x86';
                $ret['uad_not_abrand_version'] = '24';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'Macintosh') !== false) {
                $ret['platform'] = 'MacIntel';
                $ret['uad_bitness'] = '64';
                $ret['uad_architecture'] = 'x86';
                $ret['uad_not_abrand_version'] = '99';
                $ret['uad_model'] = '';
            } elseif (stripos($userAgent, 'Android')) {
                $ret['uad_bitness'] = '';
                $ret['uad_architecture'] = '';
                $l = ['Linux armv8l', 'Linux armv7l'];
                $ret['uad_not_abrand_version'] = '24';
                $ret['platform'] = $l[random_int(0, count($l) - 1)];
                $ret['uad_model'] = $ua_parser_data['device']['model'];
            }
            return $ret;
        }

        private static function getDeviceTypeFromUserAgent($userAgent) {
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
                return 'tablet';
            }

            if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
                return 'mobile';
            }

            $mobile_ua = strtolower(substr($userAgent, 0, 4));
            $mobile_agents = array(
                'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
                'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
                'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
                'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
                'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
                'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
                'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
                'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
                'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-');

            if (in_array($mobile_ua, $mobile_agents)) {
                return 'mobile';
            }

            if (strpos(strtolower($userAgent), 'opera mini') > 0) {
                return 'mobile';
            }

            return 'desktop';
        }

        private static function calculateSpeechVoiceList($UADPlatform) {
            switch ($UADPlatform) {
                case 'Android':
                    return [
                        ['name' => 'English United States', 'lang' => 'en_US', 'default' => false, 'voiceURI' => 'English United States', 'localService' => true],
                        ['name' => 'German Germany', 'lang' => 'de_DE', 'default' => false, 'voiceURI' => 'German Germany', 'localService' => true],
                        ['name' => 'English United Kingdom', 'lang' => 'en_GB', 'default' => false, 'voiceURI' => 'English United Kingdom', 'localService' => true],
                        ['name' => 'Spanish Spain', 'lang' => 'es_ES', 'default' => false, 'voiceURI' => 'Spanish Spain', 'localService' => true],
                        ['name' => 'Spanish Mexico', 'lang' => 'es_MX', 'default' => false, 'voiceURI' => 'Spanish Mexico', 'localService' => true],
                        ['name' => 'French France', 'lang' => 'fr_FR', 'default' => false, 'voiceURI' => 'French France', 'localService' => true],
                        ['name' => 'Italian Italy', 'lang' => 'it_IT', 'default' => false, 'voiceURI' => 'Italian Italy', 'localService' => true],
                        ['name' => 'Portuguese Brazil', 'lang' => 'pt_BR', 'default' => false, 'voiceURI' => 'Portuguese Brazil', 'localService' => true]
                    ];
                case 'Windows':
                    return [
                        ['name' => 'Microsoft David - English (United States)', 'lang' => 'en-US', 'default' => false, 'voiceURI' => 'Microsoft David - English (United States)', 'localService' => true],
                        ['name' => 'Microsoft Mark - English (United States)', 'lang' => 'en-US', 'default' => false, 'voiceURI' => 'Microsoft Mark - English (United States)', 'localService' => true],
                        ['name' => 'Microsoft Zira - English  (United States)', 'lang' => 'en_US', 'default' => false, 'voiceURI' => 'Microsoft Zira - English  (United States)', 'localService' => true],
                        ['name' => 'Google Deutsch', 'lang' => 'de-DE', 'default' => false, 'voiceURI' => 'Google Deutsch', 'localService' => false],
                        ['name' => 'Google US English', 'lang' => 'en-US', 'default' => false, 'voiceURI' => 'Google US English', 'localService' => false],
                        ['name' => 'Google UK English Female', 'lang' => 'en-GB', 'default' => false, 'voiceURI' => 'Google UK English Female', 'localService' => false],
                        ['name' => 'Google UK English Male', 'lang' => 'en-GB', 'default' => false, 'voiceURI' => 'Google UK English Male', 'localService' => false],
                        ['name' => 'Google español', 'lang' => 'es-ES', 'default' => false, 'voiceURI' => 'Google español', 'localService' => false],
                        ['name' => 'Google español de Estados Unidos', 'lang' => 'es-US', 'default' => false, 'voiceURI' => 'Google español de Estados Unidos', 'localService' => false],
                        ['name' => 'Google français', 'lang' => 'fr-FR', 'default' => false, 'voiceURI' => 'Google français', 'localService' => false],
                        ['name' => 'Google Bahasa Indonesia', 'lang' => 'id-ID', 'default' => false, 'voiceURI' => 'Google Bahasa Indonesia', 'localService' => false],
                        ['name' => 'Google italiano', 'lang' => 'it-IT', 'default' => false, 'voiceURI' => 'Google italiano', 'localService' => false],
                        ['name' => 'Google Nederlands', 'lang' => 'nl-NL', 'default' => false, 'voiceURI' => 'Google Nederlands', 'localService' => false],
                        ['name' => 'Google polski', 'lang' => 'pl-PL', 'default' => false, 'voiceURI' => 'Google polski', 'localService' => false],
                        ['name' => 'Google português do Brasil', 'lang' => 'pt-BR', 'default' => false, 'voiceURI' => 'Google português do Brasil', 'localService' => false],
                        ['name' => 'Google русский', 'lang' => 'ru-RU', 'default' => false, 'voiceURI' => 'Google русский', 'localService' => false],
                        ['name' => 'Google 普通话（中国大陆）', 'lang' => 'zh-CN', 'default' => false, 'voiceURI' => 'Google 普通话（中国大陆）', 'localService' => false],
                        ['name' => 'Google 粤語（香港）', 'lang' => 'zh-HK', 'default' => false, 'voiceURI' => 'Google 粤語（香港）', 'localService' => false],
                        ['name' => 'Google 國語（臺灣）', 'lang' => 'zh-TW', 'default' => false, 'voiceURI' => 'Google 國語（臺灣）', 'localService' => false]
                    ];
                default:
                    return [];
            }
            return [];
        }

    }

}