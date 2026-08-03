<?php
return [
'accepted' => ':attribute alanı kabul edilmiş olmalıdır.',
'accepted_if' => ':other :value değerindeyken :attribute alanı kabul edilmiş olmalıdır.',
'active_url' => ':attribute alanı geçerli bir URL olmalıdır.',
'after' => ':attribute alanı :date tarihinden sonraki bir tarih olmalıdır.',
'after_or_equal' => ':attribute alanı, :date tarihinden sonra veya ona eşit bir tarih olmalıdır.',
'alpha' => ':attribute alanı yalnızca harfler içermelidir.',
'alpha_dash' => ':attribute alanı yalnızca harfler, rakamlar, tireler ve alt çizgiler içermelidir.',
'alpha_num' => ':attribute alanı yalnızca harf ve rakam içermelidir.',
'any_of' => ':attribute alanı geçersizdir.',
'array' => ':attribute alanı bir dizi olmalıdır.',
'ascii' => ':attribute alanı yalnızca tek baytlı alfasayısal karakterler ve semboller içermelidir.',
'before' => ':attribute alanı, :date tarihinden önceki bir tarih olmalıdır.',
'before_or_equal' => ':attribute alanı, :date tarihinden önceki veya ona eşit bir tarih olmalıdır.',
'between' => [
    'array' => ':attribute alanı, :min ile :max arasında öğe içermelidir.',
    'file' => ':attribute alanı :min ile :max kilobayt arasında olmalıdır.',
    'numeric' => ':attribute alanı :min ile :max arasında olmalıdır.',
    'string' => ':attribute alanı :min ile :max karakter arasında olmalıdır.',
],
'boolean' => ':attribute alanı true veya false olmalıdır.',
'can' => ':attribute alanı yetkisiz bir değer içeriyor.',
'confirmed' => ':attribute alanının onayı eşleşmiyor.',
'contains' => ':attribute alanında zorunlu bir değer eksik.',
'current_password' => 'Şifre yanlış.',
'date' => ':attribute alanı geçerli bir tarih olmalı.',
'date_equals' => ':attribute alanı, :date ile eşit bir tarih olmalıdır.',
'date_format' => ':attribute alanı, :format biçimiyle eşleşmelidir.',
'decimal' => ':attribute alanı, :decimal ondalık basamağa sahip olmalıdır.',
'declined' => ':attribute alanı reddedilmiş olmalıdır.',
'declined_if' => ':other :value olduğunda :attribute alanı reddedilmiş olmalıdır.',
'different' => ':attribute alanı ile :other farklı olmalıdır.',
'digits' => ':attribute alanı :digits basamaklı olmalıdır.',
'digits_between' => ':attribute alanı :min ile :max basamak arasında olmalıdır.',
'dimensions' => ':attribute alanı geçersiz resim boyutlarına sahiptir.',
'distinct' => ':attribute alanında yinelenen bir değer var.',
'doesnt_contain' => ':attribute alanı aşağıdakilerden hiçbirini içermemelidir: :values.',
'doesnt_end_with' => ':attribute alanı aşağıdakilerden biriyle bitmemelidir: :values.',
'doesnt_start_with' => ':attribute alanı aşağıdakilerden biriyle başlamamalıdır: :values.',
'email' => ':attribute alanı geçerli bir e-posta adresi olmalıdır.',
'encoding' => ':attribute alanı :encoding ile kodlanmalıdır.',
'ends_with' => ':attribute alanı aşağıdakilerden biriyle bitmelidir: :values.',
'enum' => 'Seçilen :attribute geçersizdir.',
'exists' => 'Seçilen :attribute geçersizdir.',
'extensions' => ':attribute alanı aşağıdaki uzantılardan birine sahip olmalıdır: :values.',
'file' => ':attribute alanı bir dosya olmalıdır.',
'filled' => ':attribute alanı bir değere sahip olmalıdır.',
'gt' => [
    'array' => ':attribute alanı :value\'den fazla öğe içermelidir.',
    'file' => ':attribute alanı :value kilobayttan büyük olmalıdır.',
    'numeric' => ':attribute alanı :value değerinden büyük olmalıdır.',
    'string' => ':attribute alanı :value karakterden fazla olmalıdır.',
],
'gte' => [
    'array' => ':attribute alanı :value öğe veya daha fazlasını içermelidir.',
    'file' => ':attribute alanı :value kilobayttan büyük veya buna eşit olmalıdır.',
    'numeric' => ':attribute alanı :value\'den büyük veya buna eşit olmalıdır.',
    'string' => ':attribute alanı :value karakterden büyük veya buna eşit olmalıdır.',
],
'hex_color' => ':attribute alanı geçerli bir onaltılık renk olmalı.',
'image' => ':attribute alanı bir resim olmalı.',
'in' => 'Seçilen :attribute geçersiz.',
'in_array' => ':attribute alanı :other içinde bulunmalı.',
'in_array_keys' => ':attribute alanı, aşağıdaki anahtarlardan en az birini içermelidir: :values.',
'integer' => ':attribute alanı bir tamsayı olmalıdır.',
'ip' => ':attribute alanı geçerli bir IP adresi olmalıdır.',
'ipv4' => ':attribute alanı geçerli bir IPv4 adresi olmalıdır.',
'ipv6' => ':attribute alanı geçerli bir IPv6 adresi olmalıdır.',
'json' => ':attribute alanı geçerli bir JSON dizesi olmalıdır.',
'list' => ':attribute alanı bir liste olmalıdır.',
'lowercase' => ':attribute alanı küçük harflerle yazılmalıdır.',
'lt' => [
    'array' => ':attribute alanı :value\'den az öğe içermelidir.',
    'file' => ':attribute alanı :value kilobayttan küçük olmalıdır.',
    'numeric' => ':attribute alanı :value\'den küçük olmalıdır.',
    'string' => ':attribute alanı :value karakterden az olmalıdır.',
],
'lte' => [
    'array' => ':attribute alanı :value\'den fazla öğe içermemelidir.',
    'file' => ':attribute alanı :value kilobayttan az veya buna eşit olmalıdır.',
    'numeric' => ':attribute alanı :value\'den az veya buna eşit olmalıdır.',
    'string' => ':attribute alanı :value karakterden az veya buna eşit olmalıdır.',
],
'mac_address' => ':attribute alanı geçerli bir MAC adresi olmalıdır.',
'max' => [
    'array' => ':attribute alanı :max öğeden fazla olmamalıdır.',
    'file' => ':attribute alanı :max kilobayttan fazla olmamalıdır.',
    'numeric' => ':attribute alanı :max değerinden fazla olmamalıdır.',
    'string' => ':attribute alanı :max karakterden fazla olmamalıdır.',
],
'max_digits' => ':attribute alanı :max basamaktan fazla olmamalıdır.' ,
'mimes' => ':attribute alanı şu türde bir dosya olmalıdır: :values.',
'mimetypes' => ':attribute alanı şu türde bir dosya olmalıdır: :values.',
'min' => [
    'array' => ':attribute alanı en az :min öğe içermelidir.',
    'file' => ':attribute alanı en az :min kilobayt olmalıdır.',
    'numeric' => ':attribute alanı en az :min olmalıdır.',
    'string' => ':attribute alanı en az :min karakter içermelidir.',
],
'min_digits' => ':attribute alanı en az :min basamak içermelidir.',
'missing' => ':attribute alanı boş olmalıdır.',
'missing_if' => ':other :value değerindeyken :attribute alanı boş olmalıdır.',
'missing_unless' => ':other :value değerinde olmadığı sürece :attribute alanı boş olmalıdır.' ,
'missing_with' => ':values mevcut olduğunda :attribute alanı boş olmalıdır.',
'missing_with_all' => ':values mevcut olduğunda :attribute alanı boş olmalıdır.',
'multiple_of' => ':attribute alanı :value\'nin katı olmalıdır.',
'not_in' => 'Seçilen :attribute geçersiz.',
'not_regex' => ':attribute alanının biçimi geçersiz.',
'numeric' => ':attribute alanı bir sayı olmalıdır.',
'password' => [
    'letters' => ':attribute alanı en az bir harf içermelidir.',
    'mixed' => ':attribute alanı en az bir büyük harf ve bir küçük harf içermelidir.',
    'numbers' => ':attribute alanı en az bir sayı içermelidir.',
    'symbols' => ':attribute alanı en az bir sembol içermelidir.',
    'uncompromised' => 'Belirtilen :attribute, bir veri sızıntısında yer almıştır. Lütfen farklı bir :attribute seçin.',
],
'present' => ':attribute alanı mevcut olmalıdır.',
'present_if' => ':other :value değerindeyken :attribute alanı mevcut olmalıdır.',
'present_unless' => ':other :value değerinde olmadığı sürece :attribute alanı mevcut olmalıdır.',
'present_with' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
'present_with_all' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
'prohibited' => ':attribute alanı yasaktır.',
'prohibited_if' => ':other :value olduğunda :attribute alanı yasaktır.',
'prohibited_if_accepted' => ':other kabul edildiğinde :attribute alanı yasaktır.',
'prohibited_if_declined' => ':other reddedildiğinde :attribute alanı yasaktır.',
'prohibited_unless' => ':other, :values içinde yer almadıkça :attribute alanı yasaktır.',
'prohibits' => ':attribute alanı, :other\'ın bulunmasını yasaklar.',
'regex' => ':attribute alanı biçimi geçersiz.',
'required' => ':attribute alanı zorunludur.',
'required_array_keys' => ':attribute alanı, :values için girişler içermelidir.',
'required_if' => ':other :value olduğunda :attribute alanı zorunludur.',
'required_if_accepted' => ':other kabul edildiğinde :attribute alanı zorunludur.',
'required_if_declined' => ':other reddedildiğinde :attribute alanı zorunludur.',
'required_unless' => ':other, :values içinde yer almadıkça :attribute alanı zorunludur.',
'required_with' => ':values mevcut olduğunda :attribute alanı zorunludur.',
'required_with_all' => ':values mevcut olduğunda :attribute alanı zorunludur.',
'required_without' => ':values mevcut olmadığında :attribute alanı zorunludur.',
'required_without_all' => ':values\'ın hiçbiri mevcut olmadığında :attribute alanı zorunludur.',
'same' => ':attribute alanı :other ile eşleşmelidir.', 
'size' => [
    'array' => ':attribute alanı :size adet öğe içermelidir.',
    'file' => ':attribute alanı :size kilobayt olmalıdır.',
    'numeric' => ':attribute alanı :size değerinde olmalıdır.',
    'string' => ':attribute alanı :size karakter uzunluğunda olmalıdır.',
],
'starts_with' => ':attribute alanı aşağıdakilerden biriyle başlamalıdır: :values.',
'string' => ':attribute alanı bir dize olmalıdır.',
'timezone' => ':attribute alanı geçerli bir saat dilimi olmalıdır.',
'unique' => ':attribute zaten alınmış.',
'uploaded' => ':attribute yüklenemedi.',
'uppercase' => ':attribute alanı büyük harflerle yazılmalıdır.',
'url' => ':attribute alanı geçerli bir URL olmalıdır.',
'ulid' => ':attribute alanı geçerli bir ULID olmalıdır.',
'uuid' => ':attribute alanı geçerli bir UUID olmalıdır.',

/*
|----------------------------- ---------------------------------------------
| Özel Doğrulama Dil Satırları
|-------------------------------------------- ------------------------------
|
| Burada, satırları adlandırmak için
| “attribute.rule” kuralını kullanarak öznitelikler için özel doğrulama mesajları belirtebilirsiniz. Bu, belirli bir öznitelik kuralı için
| belirli bir özel dil satırını hızlı bir şekilde belirlemenizi sağlar.
|
*/

'custom' => [
    'attribute-name' => [
        'rule-name' => 'custom-message',
    ],
],

/*
|---------------------------------------------------- ----------------------
| Özel Doğrulama Öznitelikleri
|------------------------------------------------------------------ --------
|
| Aşağıdaki dil satırları, öznitelik yer tutucumuzu
| “email” yerine “E-posta Adresi” gibi okuyucu için daha anlaşılır bir ifadeyle
| değiştirmek için kullanılır. Bu, mesajımızı daha anlamlı hale getirmemize yardımcı olur.
|
*/

'attributes' => [],

];
