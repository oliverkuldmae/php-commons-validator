<?php

namespace PHPCommons\Validator\Tests\Rule;

use PHPCommons\Validator\Rule\Email;
use PHPCommons\Validator\Tests\ResultPair;
use PHPUnit\Framework\TestCase;
use function chr;

class EmailTest extends TestCase {

    /**
     * @var Email
     */
    protected $validator;

    protected function setUp() {
        $this->validator = Email::getInstance();
    }

    public function testEmail() {
        $this->assertTrue($this->validator->isValid('jsmith@apache.org'));
    }

    /**
     * Tests the email validation with numeric domains.
     */
    public function testEmailWithNumericAddress() {
        $this->assertTrue($this->validator->isValid("someone@[216.109.118.76]"));

        $this->assertTrue($this->validator->isValid("someone@yahoo.com"));
    }

    /**
     * Tests the e-mail validation.
     */
    public function testEmailExtension() {
        $this->assertTrue($this->validator->isValid("jsmith@apache.org"));

        $this->assertTrue($this->validator->isValid("jsmith@apache.com"));

        $this->assertTrue($this->validator->isValid("jsmith@apache.net"));

        $this->assertTrue($this->validator->isValid("jsmith@apache.info"));

        $this->assertFalse($this->validator->isValid("jsmith@apache."));

        $this->assertFalse($this->validator->isValid("jsmith@apache.c"));

        $this->assertTrue($this->validator->isValid("someone@yahoo.museum"));

        $this->assertFalse($this->validator->isValid("someone@yahoo.mu-seum"));
    }

    /**
     * <p>Tests the e-mail validation with a dash in
     * the address.</p>
     */
    public function testEmailWithDash() {
        $this->assertTrue($this->validator->isValid("andy.noble@data-workshop.com"));

        $this->assertFalse($this->validator->isValid("andy-noble@data-workshop.-com"));

        $this->assertFalse($this->validator->isValid("andy-noble@data-workshop.c-om"));

        $this->assertFalse($this->validator->isValid("andy-noble@data-workshop.co-m"));
    }

    /**
     * Tests the e-mail validation with a dot at the end of
     * the address.
     */
    public function testEmailWithDotEnd() {
        $this->assertFalse($this->validator->isValid("andy.noble@data-workshop.com."));
    }

    /**
     * Tests the e-mail validation with an RCS-noncompliant character in
     * the address.
     */
    public function testEmailWithBogusCharacter() {
        // todo this should pass? \u008f is Single Shift Three https://unicode-table.com/en/008F/
        // $this->assertFalse($this->validator->isValid("andy.noble@\u{008f}data-workshop.com"));

        // The ' character is valid in an email username.
        $this->assertTrue($this->validator->isValid("andy.o'reilly@data-workshop.com"));

        // But not in the domain name.
        $this->assertFalse($this->validator->isValid("andy@o'reilly.data-workshop.com"));

        // The + character is valid in an email username.
        $this->assertTrue($this->validator->isValid("foo+bar@i.am.not.in.us.example.com"));

        // But not in the domain name
        $this->assertFalse($this->validator->isValid("foo+bar@example+3.com"));

        // Domains with only special characters aren't allowed (VALIDATOR-286)
        $this->assertFalse($this->validator->isValid("test@%*.com"));
        $this->assertFalse($this->validator->isValid("test@^&#.com"));

    }

    public function testVALIDATOR_315() {
        $this->assertFalse($this->validator->isValid("me@at&t.net"));
        $this->assertTrue($this->validator->isValid("me@att.net")); // Make sure TLD is not the cause of the failure
    }

    public function testVALIDATOR_278() {
        $this->assertFalse($this->validator->isValid("someone@-test.com"));// hostname starts with dash/hyphen
        $this->assertFalse($this->validator->isValid("someone@test-.com"));// hostname ends with dash/hyphen
    }

    public function testValidator235() {
        $this->assertTrue($this->validator->isValid("someone@xn--d1abbgf6aiiy.xn--p1ai"), "xn--d1abbgf6aiiy.xn--p1ai should validate");
        $this->assertTrue($this->validator->isValid("someone@президент.рф"), "президент.рф should validate");
        $this->assertTrue($this->validator->isValid("someone@www.b\u{00fc}cher.ch"), "www.b\u{00fc}cher.ch should validate");
        // todo this should pass? \uFFFD is also a valid unicode symbol
        // $this->assertFalse($this->validator->isValid("someone@www.\u{FFFD}.ch"), "www.\u{FFFD}.ch FFFD should fail");
        $this->assertTrue($this->validator->isValid("someone@www.b\u{00fc}cher.ch"), "www.b\u{00fc}cher.ch should validate");
    }

    /**
     * Tests the email validation with commas.
     */
    public function testEmailWithCommas() {
        $this->assertFalse($this->validator->isValid("joeblow@apa,che.org"));

        $this->assertFalse($this->validator->isValid("joeblow@apache.o,rg"));

        $this->assertFalse($this->validator->isValid("joeblow@apache,org"));

    }

    /**
     * Tests the email validation with spaces.
     */
    public function testEmailWithSpaces() {
        $this->assertFalse($this->validator->isValid("joeblow @apache.org")); // TODO - this should be valid?

        $this->assertFalse($this->validator->isValid("joeblow@ apache.org"));

        $this->assertTrue($this->validator->isValid(" joeblow@apache.org")); // TODO - this should be valid?

        $this->assertTrue($this->validator->isValid("joeblow@apache.org "));

        $this->assertFalse($this->validator->isValid("joe blow@apache.org "));

        $this->assertFalse($this->validator->isValid("joeblow@apa che.org "));

    }

    /**
     * Tests the email validation with ascii control characters.
     * (i.e. Ascii chars 0 - 31 and 127)
     */
    public function testEmailWithControlChars() {
        for ($c = 0; $c < 32; $c++) {
            $this->assertFalse($this->validator->isValid("foo" . chr($c) . "bar@domain.com"), 'Test control char ' . chr($c));
        }

        $this->assertFalse($this->validator->isValid("foo" . chr(127) . "bar@domain.com"), 'Test control char 127');
    }

    /**
     * Test that @localhost and @localhost.localdomain
     *  addresses are declared as valid when requested.
     */
    public function testEmailLocalhost() {
        // Check the default is not to allow
        $noLocal = Email::getInstance(false);
        $allowLocal = Email::getInstance(true);
        $this->assertEquals($this->validator, $noLocal);

        // Depends on the validator
        $this->assertTrue(
            $allowLocal->isValid("joe@localhost.localdomain"),
            "@localhost.localdomain should be accepted but wasn't"
        );

        $this->assertTrue(
            $allowLocal->isValid("joe@localhost"),
            "@localhost should be accepted but wasn't"
        );

        $this->assertFalse(
            $noLocal->isValid("joe@localhost.localdomain"),
            "@localhost.localdomain should be accepted but wasn't"
        );
        $this->assertFalse(
            $noLocal->isValid("joe@localhost"),
            "@localhost should be accepted but wasn't"
        );
    }

    /**
     * VALIDATOR-296 - A / or a ! is valid in the user part,
     *  but not in the domain part
     */
    public function testEmailWithSlashes() {
        $this->assertTrue(
            $this->validator->isValid("joe!/blow@apache.org"),
            "/ and ! valid in username"
        );

        $this->assertFalse(
            $this->validator->isValid("joe@ap/ache.org"),
            "/ not valid in domain"
        );

        $this->assertFalse(
            $this->validator->isValid("joe@apac!he.org"),
            "! not valid in domain"
        );
    }

    /**
     * Write this test according to parts of RFC, as opposed to the type of character
     * that is being tested.
     */
    public function testEmailUserName() {
        $this->assertTrue($this->validator->isValid("joe1blow@apache.org"));

        $this->assertTrue($this->validator->isValid("joe\$blow@apache.org"));

        $this->assertTrue($this->validator->isValid("joe-@apache.org"));

        $this->assertTrue($this->validator->isValid("joe_@apache.org"));

        $this->assertTrue($this->validator->isValid("joe+@apache.org")); // + is valid unquoted

        $this->assertTrue($this->validator->isValid("joe!@apache.org")); // ! is valid unquoted

        $this->assertTrue($this->validator->isValid("joe*@apache.org")); // * is valid unquoted

        $this->assertTrue($this->validator->isValid("joe'@apache.org")); // ' is valid unquoted

        $this->assertTrue($this->validator->isValid("joe%45@apache.org")); // % is valid unquoted

        $this->assertTrue($this->validator->isValid("joe?@apache.org")); // ? is valid unquoted

        $this->assertTrue($this->validator->isValid("joe&@apache.org")); // & ditto

        $this->assertTrue($this->validator->isValid("joe=@apache.org")); // = ditto

        $this->assertTrue($this->validator->isValid("+joe@apache.org")); // + is valid unquoted

        $this->assertTrue($this->validator->isValid("!joe@apache.org")); // ! is valid unquoted

        $this->assertTrue($this->validator->isValid("*joe@apache.org")); // * is valid unquoted

        $this->assertTrue($this->validator->isValid("'joe@apache.org")); // ' is valid unquoted

        $this->assertTrue($this->validator->isValid("%joe45@apache.org")); // % is valid unquoted

        $this->assertTrue($this->validator->isValid("?joe@apache.org")); // ? is valid unquoted

        $this->assertTrue($this->validator->isValid("&joe@apache.org")); // & ditto

        $this->assertTrue($this->validator->isValid("=joe@apache.org")); // = ditto

        $this->assertTrue($this->validator->isValid("+@apache.org")); // + is valid unquoted

        $this->assertTrue($this->validator->isValid("!@apache.org")); // ! is valid unquoted

        $this->assertTrue($this->validator->isValid("*@apache.org")); // * is valid unquoted

        $this->assertTrue($this->validator->isValid("'@apache.org")); // ' is valid unquoted

        $this->assertTrue($this->validator->isValid("%@apache.org")); // % is valid unquoted

        $this->assertTrue($this->validator->isValid("?@apache.org")); // ? is valid unquoted

        $this->assertTrue($this->validator->isValid("&@apache.org")); // & ditto

        $this->assertTrue($this->validator->isValid("=@apache.org")); // = ditto


        //UnQuoted Special characters are invalid

        $this->assertFalse($this->validator->isValid("joe.@apache.org")); // . not allowed at end of local part

        $this->assertFalse($this->validator->isValid(".joe@apache.org")); // . not allowed at start of local part

        $this->assertFalse($this->validator->isValid(".@apache.org")); // . not allowed alone

        $this->assertTrue($this->validator->isValid("joe.ok@apache.org")); // . allowed embedded

        $this->assertFalse($this->validator->isValid("joe..ok@apache.org")); // .. not allowed embedded

        $this->assertFalse($this->validator->isValid("..@apache.org")); // .. not allowed alone

        $this->assertFalse($this->validator->isValid("joe(@apache.org"));

        $this->assertFalse($this->validator->isValid("joe)@apache.org"));

        $this->assertFalse($this->validator->isValid("joe,@apache.org"));

        $this->assertFalse($this->validator->isValid("joe;@apache.org"));


        //Quoted Special characters are valid
        $this->assertTrue($this->validator->isValid("\"joe.\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\".joe\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe+\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe!\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe*\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe'\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe(\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe)\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe,\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe%45\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe;\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe?\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe&\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"joe=\"@apache.org"));

        $this->assertTrue($this->validator->isValid("\"..\"@apache.org"));

        // escaped quote character valid in quoted string
        $this->assertTrue($this->validator->isValid("\"john\\\"doe\"@apache.org"));

        $this->assertTrue($this->validator->isValid("john56789.john56789.john56789.john56789.john56789.john56789.john@example.com"));

        $this->assertFalse($this->validator->isValid("john56789.john56789.john56789.john56789.john56789.john56789.john5@example.com"));

        $this->assertTrue($this->validator->isValid("\\>escape\\\\special\\^characters\\<@example.com"));

        $this->assertTrue($this->validator->isValid("Abc\\@def@example.com"));

        $this->assertFalse($this->validator->isValid("Abc@def@example.com"));

        $this->assertTrue($this->validator->isValid("space\\ monkey@example.com"));
    }

    /**
     * These test values derive directly from RFC 822 &
     * Mail::RFC822::Address & RFC::RFC822::Address perl test.pl
     * For traceability don't combine these test values with other tests.
     *
     * @return ResultPair[]
     */
    private static function getTestEmailFromPerl() : array {
        return [
            new ResultPair("abigail@example.com", true),
            new ResultPair("abigail@example.com ", true),
            new ResultPair(" abigail@example.com", true),
            new ResultPair("abigail @example.com ", true),
            new ResultPair("*@example.net", true),
            new ResultPair("\"\\\"\"@foo.bar", true),
            new ResultPair("fred&barny@example.com", true),
            new ResultPair("---@example.com", true),
            new ResultPair("foo-bar@example.net", true),
            new ResultPair("\"127.0.0.1\"@[127.0.0.1]", true),
            new ResultPair("Abigail <abigail@example.com>", true),
            new ResultPair("Abigail<abigail@example.com>", true),
            new ResultPair("Abigail<@a,@b,@c:abigail@example.com>", true),
            new ResultPair("\"This is a phrase\"<abigail@example.com>", true),
            new ResultPair("\"Abigail \"<abigail@example.com>", true),
            new ResultPair("\"Joe & J. Harvey\" <example @Org>", true),
            new ResultPair("Abigail <abigail @ example.com>", true),
            new ResultPair("Abigail made this <  abigail   @   example  .    com    >", true),
            new ResultPair("Abigail(the bitch)@example.com", true),
            new ResultPair("Abigail <abigail @ example . (bar) com >", true),
            new ResultPair("Abigail < (one)  abigail (two) @(three)example . (bar) com (quz) >", true),
            new ResultPair("Abigail (foo) (((baz)(nested) (comment)) ! ) < (one)  abigail (two) @(three)example . (bar) com (quz) >", true),
            new ResultPair("Abigail <abigail(fo\\(o)@example.com>", true),
            new ResultPair("Abigail <abigail(fo\\)o)@example.com> ", true),
            new ResultPair("(foo) abigail@example.com", true),
            new ResultPair("abigail@example.com (foo)", true),
            new ResultPair("\"Abi\\\"gail\" <abigail@example.com>", true),
            new ResultPair("abigail@[example.com]", true),
            new ResultPair("abigail@[exa\\[ple.com]", true),
            new ResultPair("abigail@[exa\\]ple.com]", true),
            new ResultPair("\":sysmail\"@  Some-Group. Some-Org", true),
            new ResultPair("Muhammed.(I am  the greatest) Ali @(the)Vegas.WBA", true),
            new ResultPair("mailbox.sub1.sub2@this-domain", true),
            new ResultPair("sub-net.mailbox@sub-domain.domain", true),
            new ResultPair("name:;", true),
            new ResultPair("':;", true),
            new ResultPair("name:   ;", true),
            new ResultPair("Alfred Neuman <Neuman@BBN-TENEXA>", true),
            new ResultPair("Neuman@BBN-TENEXA", true),
            new ResultPair("\"George, Ted\" <Shared@Group.Arpanet>", true),
            new ResultPair("Wilt . (the  Stilt) Chamberlain@NBA.US", true),
            new ResultPair("Cruisers:  Port@Portugal, Jones@SEA;", true),
            new ResultPair("$@[]", true),
            new ResultPair("*()@[]", true),
            new ResultPair("\"quoted ( brackets\" ( a comment )@example.com", true),
            new ResultPair("\"Joe & J. Harvey\"\\x0D\\x0A     <ddd\\@ Org>", true),
            new ResultPair("\"Joe &\\x0D\\x0A J. Harvey\" <ddd \\@ Org>", true),
            new ResultPair(
                "Gourmets:  Pompous Person <WhoZiWhatZit\\@Cordon-Bleu>,\\x0D\\x0A" .
                "        Childs\\@WGBH.Boston, \"Galloping Gourmet\"\\@\\x0D\\x0A" .
                "        ANT.Down-Under (Australian National Television),\\x0D\\x0A" .
                "        Cheapie\\@Discount-Liquors;", true
            ),
            new ResultPair("   Just a string", false),
            new ResultPair("string", false),
            new ResultPair("(comment)", false),
            new ResultPair("()@example.com", false),
            new ResultPair("fred(&)barny@example.com", false),
            new ResultPair("fred\\ barny@example.com", false),
            new ResultPair("Abigail <abi gail @ example.com>", false),
            new ResultPair("Abigail <abigail(fo(o)@example.com>", false),
            new ResultPair("Abigail <abigail(fo)o)@example.com>", false),
            new ResultPair("\"Abi\"gail\" <abigail@example.com>", false),
            new ResultPair("abigail@[exa]ple.com]", false),
            new ResultPair("abigail@[exa[ple.com]", false),
            new ResultPair("abigail@[exaple].com]", false),
            new ResultPair("abigail@", false),
            new ResultPair("@example.com", false),
            new ResultPair("phrase: abigail@example.com abigail@example.com ;", false),
            new ResultPair("invalid�char@example.com", false)
        ];
    }

    /**
     * Write this test based on perl Mail::RFC822::Address
     * which takes its example email address directly from RFC822
     *
     * FIXME This test fails so disable it with a leading _ for 1.1.4 release.
     * The real solution is to fix the email parsing.
     */
    public function _testEmailFromPerl() {
        $testEmailsFromPerl = self::getTestEmailFromPerl();
        foreach ($testEmailsFromPerl as $testEmail) {
            if ($testEmail->isValid) {
                $this->assertTrue('Should be OK: ' . $testEmail->item, $this->validator->isValid($testEmail->item));
            } else {
                $this->assertFalse('Should fail: ' . $testEmail->item, $this->validator->isValid($testEmail->item));
            }
        }
    }

    public function testValidator293() {
        $this->assertTrue($this->validator->isValid("abc-@abc.com"));
        $this->assertTrue($this->validator->isValid("abc_@abc.com"));
        $this->assertTrue($this->validator->isValid("abc-def@abc.com"));
        $this->assertTrue($this->validator->isValid("abc_def@abc.com"));
        $this->assertFalse($this->validator->isValid("abc@abc_def.com"));
    }

    public function testValidator365() {
        $this->assertFalse(
            $this->validator->isValid(
                "Loremipsumdolorsitametconsecteturadipiscingelit.Nullavitaeligulamattisrhoncusnuncegestasmattisleo." .
                "Donecnonsapieninmagnatristiquedictumaacturpis.Fusceorciduifacilisisutsapieneuconsequatpharetralectus." .
                "Quisqueenimestpulvinarutquamvitaeportamattisex.Nullamquismaurisplaceratconvallisjustoquisportamauris." .
                "Innullalacusconvalliseufringillautvenenatissitametdiam.Maecenasluctusligulascelerisquepulvinarfeugiat." .
                "Sedmolestienullaaliquetorciluctusidpharetranislfinibus.Suspendissemalesuadatinciduntduisitametportaarcusollicitudinnec." .
                "Donecetmassamagna.Curabitururnadiampretiumveldignissimporttitorfringillaeuneque." .
                "Duisantetelluspharetraidtinciduntinterdummolestiesitametfelis.Utquisquamsitametantesagittisdapibusacnonodio." .
                "Namrutrummolestiediamidmattis.Cumsociisnatoquepenatibusetmagnisdisparturientmontesnasceturridiculusmus." .
                "Morbiposueresedmetusacconsectetur.Etiamquisipsumvitaejustotempusmaximus.Sedultriciesplaceratvolutpat." .
                "Integerlacuslectusmaximusacornarequissagittissitametjusto." .
                "Cumsociisnatoquepenatibusetmagnisdisparturientmontesnasceturridiculusmus.Maecenasindictumpurussedrutrumex.Nullafacilisi." .
                "Integerfinibusfinibusmietpharetranislfaucibusvel.Maecenasegetdolorlacinialobortisjustovelullamcorpersem." .
                "Vivamusaliquetpurusidvariusornaresapienrisusrutrumnisitinciduntmollissemnequeidmetus." .
                "Etiamquiseleifendpurus.Nuncfelisnuncscelerisqueiddignissimnecfinibusalibero." .
                "Nuncsemperenimnequesitamethendreritpurusfacilisisac.Maurisdapibussemperfelisdignissimgravida." .
                "Aeneanultricesblanditnequealiquamfinibusodioscelerisqueac.Aliquamnecmassaeumaurisfaucibusfringilla." .
                "Etiamconsequatligulanisisitametaliquamnibhtemporquis.Nuncinterdumdignissimnullaatsodalesarcusagittiseu." .
                "Proinpharetrametusneclacuspulvinarsedvolutpatliberoornare.Sedligulanislpulvinarnonlectuseublanditfacilisisante." .
                "Sedmollisnislalacusauctorsuscipit.Inhachabitasseplateadictumst.Phasellussitametvelittemporvenenatisfeliseuegestasrisus." .
                "Aliquameteratsitametnibhcommodofinibus.Morbiefficiturodiovelpulvinariaculis." .
                "Aeneantemporipsummassaaconsecteturturpisfaucibusultrices.Praesentsodalesmaurisquisportafermentum." .
                "Etiamnisinislvenenatisvelauctorutullamcorperinjusto.Proinvelligulaerat.Phasellusvestibulumgravidamassanonfeugiat." .
                "Maecenaspharetraeuismodmetusegetefficitur.Suspendisseamet@gmail.com"
            )
        );
    }

    /**
     * Tests the e-mail validation with a user at a TLD
     *
     * http://tools.ietf.org/html/rfc5321#section-2.3.5
     * (In the case of a top-level domain used by itself in an
     * email address, a single string is used without any dots)
     */
    public function testEmailAtTLD() {
        $validator = Email::getInstance(false, true);
        $this->assertTrue($validator->isValid("test@com"));
    }

    public function testValidator359() {
        $validator = Email::getInstance(false, true);
        $this->assertFalse($validator->isValid("test@.com"));
    }

    public function testValidator374() {
        $this->assertTrue($this->validator->isValid("abc@school.school"));
    }

}