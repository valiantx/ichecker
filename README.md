* a simple input checker, three methods are supplied:

  * "check": check single key, support type/required/nonempty/default
  * "checkKeyRule": check single key, support range/convert, in additional to aboves
  * "checkRule": check multi keys

* examples:

  ```php
  // (1) single key check, 'attr_name' is a string, required, not empty
  $strAttrName = IChecker::check($arrInput, 'attr_name', IChecker::TYPE_STRING, true, true);

  // (2) single key check, 'ext' can be any type, optional, not empty, default value array('ext' => '')
  $mixExt = IChecker::check($arrInput, 'ext', IChecker::TYPE_ANY, false, true, ['ext' => '']);

  // (3) single key check, 'version' is a version string, required, must be >= '5.1.0.10'
  $strVersion = IChecker::checkKeyRule($arrInput, 'version', [
      IChecker::RULE_TYPE => IChecker::TYPE_VERSION,
      IChecker::RULE_REQUIRED => true,
      IChecker::RULE_RANGE => ['>=' => '5.1.0.10'],
  ]);

  // (4) multi keys' check
  $arrInput = IChecker::checkRule($arrInput, [
      // (4.1) 'key1' is an integer, required, must be >= 100 and <= 200, or <= 10, or in [300, 400, 500]
      'key1' => [
          IChecker::RULE_TYPE => IChecker::TYPE_INTEGER,
          IChecker::RULE_REQUIRED => true,
          IChecker::RULE_RANGE => [
              ['>=' => 100, '<=' => 200],
              ['<=' => 10,],
              ['in' => [300, 400, 500,]]
          ],
      ],

      // (4.2) 'key2' ia an optional associated array, in which, key x and y is required, and they must be numerical
      'key2' => [
          IChecker::RULE_TYPE => [
              'x' => [
                  IChecker::RULE_TYPE => IChecker::TYPE_NUMERIC,
                  IChecker::RULE_REQUIRED => true,
              ],
              'y' => [
                  IChecker::RULE_TYPE => IChecker::TYPE_NUMERIC,
                  IChecker::RULE_REQUIRED => true,
              ],
          ],
      ],

      // (4.3) 'key3' is an optional numerical array, and every element will be convert to int after checking
      'key3' => [
          IChecker::RULE_TYPE => [
              '...' => [
                  IChecker::RULE_TYPE => IChecker::TYPE_NUMERIC,
                  IChecker::RULE_CONVERT => 'intval',
              ],
          ],
      ],

      // (4.4) 'key4' is a datetime string, or an integer > 1.5E9
      'key4' => [
          [
              IChecker::RULE_TYPE => IChecker::TYPE_INTEGER,
              IChecker::RULE_RANGE => ['>' => 1.5E9],
          ],
          [
              IChecker::RULE_TYPE => IChecker::TYPE_DATETIME,
              IChecker::RULE_CONVERT => 'strtotime'
          ],
      ],
  ]);

  // (5) multi keys' check, with several optional rules
  $arrInput = IChecker::checkRule($arrInput, [
      [
          'key5' => [
              IChecker::RULE_TYPE => IChecker::TYPE_STRING,
              IChecker::RULE_REQUIRED => true,
          ],
      ],
      [
          'key6' => [
              IChecker::RULE_TYPE => IChecker::TYPE_STRING,
              IChecker::RULE_REQUIRED => true,
          ],
      ],
  ]);

  // (6) multi keys' check, and the output will filtered with the rule's keys
  $arrOutput = IChecker::checkRule($arrInput, [
      'key7' => [
          IChecker::RULE_TYPE => IChecker::TYPE_INTEGER,
          IChecker::RULE_REQUIRED => true,
          IChecker::RULE_CONVERT => 'intval',
      ],
      'key8' => [
          IChecker::RULE_TYPE => IChecker::TYPE_INTEGER,
          IChecker::RULE_DEFAULT => 1,
          IChecker::RULE_CONVERT => 'intval',
      ],
  ], [
      IChecker::OPT_FILTER => true,
  ]);
  ```

  ​