<?php
namespace CanadaSatellite\Bambora;
# 2021-07-14 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
# "Refactor the `Schogini_Beanstream` module": https://github.com/canadasatellite-ca/bambora/issues/1
final class Regions {
	/**
	 * 2021-07-14
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return array(string => string)
	 */
	static function ca() {return [
		 'Alberta' => 'AB'
		 ,'British Columbia' => 'BC'
		 ,'Manitoba' => 'MB'
		 ,'New Brunswick' => 'NB'
		 ,'Newfoundland and Labrador' => 'NL'
		 ,'Northwest Territories' => 'NT'
		 ,'Nova Scotia' => 'NS'
		 ,'Nunavut' => 'NU'
		 ,'Ontario' => 'ON'
		 ,'Prince Edward Island' => 'PE'
		 ,'Quebec' => 'QC'
		 ,'Saskatchewan' => 'SK'
		 ,'Yukon Territory' => 'YT'
	];}

	/**
	 * 2021-07-14
	 * @used-by \CanadaSatellite\Bambora\Facade::api()
	 * @return array(string => string)
	 */
	static function us() {return [
		'Alabama' => 'AL'
		,'Alaska' => 'AK'
		,'American Samoa' => 'AS'
		,'Arizona' => 'AZ'
		,'Arkansas' => 'AR'
		,'Armed Forces Africa' => 'AF'
		,'Armed Forces Americas' => 'AA'
		,'Armed Forces Canada' => 'AC'
		,'Armed Forces Europe' => 'AE'
		,'Armed Forces Middle East' => 'AM'
		,'Armed Forces Pacific' => 'AP'
		,'California' => 'CA'
		,'Colorado' => 'CO'
		,'Connecticut' => 'CT'
		,'Delaware' => 'DE'
		,'District of Columbia' => 'DC'
		,'Federated States Of Micronesia' => 'FM'
		,'Florida' => 'FL'
		,'Georgia' => 'GA'
		,'Guam' => 'GU'
		,'Hawaii' => 'HI'
		,'Idaho' => 'ID'
		,'Illinois' => 'IL'
		,'Indiana' => 'IN'
		,'Iowa' => 'IA'
		,'Kansas' => 'KS'
		,'Kentucky' => 'KY'
		,'Louisiana' => 'LA'
		,'Maine' => 'ME'
		,'Marshall Islands' => 'MH'
		,'Maryland' => 'MD'
		,'Massachusetts' => 'MA'
		,'Michigan' => 'MI'
		,'Minnesota' => 'MN'
		,'Mississippi' => 'MS'
		,'Missouri' => 'MO'
		,'Montana' => 'MT'
		,'Nebraska' => 'NE'
		,'Nevada' => 'NV'
		,'New Hampshire' => 'NH'
		,'New Jersey' => 'NJ'
		,'New Mexico' => 'NM'
		,'New York' => 'NY'
		,'North Carolina' => 'NC'
		,'North Dakota' => 'ND'
		,'Northern Mariana Islands' => 'MP'
		,'Ohio' => 'OH'
		,'Oklahoma' => 'OK'
		,'Oregon' => 'OR'
		,'Palau' => 'PW'
		,'Pennsylvania' => 'PA'
		,'Puerto Rico' => 'PR'
		,'Rhode Island' => 'RI'
		,'South Carolina' => 'SC'
		,'South Dakota' => 'SD'
		,'Tennessee' => 'TN'
		,'Texas' => 'TX'
		,'Utah' => 'UT'
		,'Vermont' => 'VT'
		,'Virgin Islands' => 'VI'
		,'Virginia' => 'VA'
		,'Washington' => 'WA'
		,'West Virginia' => 'WV'
		,'Wisconsin' => 'WI'
		,'Wyoming' => 'WY'
	];}
}