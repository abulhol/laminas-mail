<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mail;

use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\Mail\Exception\InvalidArgumentException;
use Laminas\Mail\Header;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Mail
 * @covers \Laminas\Mail\AddressList<extended>
 */
class AddressListTest extends TestCase
{
    /** @var AddressList */
    private $list;

    public function setUp()
    {
        $this->list = new AddressList();
    }

    public function testImplementsCountable()
    {
        $this->assertInstanceOf(\Countable::class, $this->list);
    }

    public function testIsEmptyByDefault()
    {
        $this->assertEquals(0, count($this->list));
    }

    public function testAddingEmailsIncreasesCount()
    {
        $this->list->add('test@example.com');
        $this->assertEquals(1, count($this->list));
    }

    public function testAddingEmailFromStringIncreasesCount()
    {
        $this->list->addFromString('test@example.com');
        $this->assertEquals(1, count($this->list));
    }

    public function testImplementsTraversable()
    {
        $this->assertInstanceOf(\Traversable::class, $this->list);
    }

    public function testHasReturnsFalseWhenAddressNotInList()
    {
        $this->assertFalse($this->list->has('foo@example.com'));
    }

    public function testHasReturnsTrueWhenAddressInList()
    {
        $this->list->add('test@example.com');
        $this->assertTrue($this->list->has('test@example.com'));
    }

    public function testGetReturnsFalseWhenEmailNotFound()
    {
        $this->assertFalse($this->list->get('foo@example.com'));
    }

    public function testThrowExceptionOnInvalidInputAdd()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('add expects an email address or Laminas\Mail\Address object');
        $this->list->add(null);
    }

    public function testThrowExceptionOnInvalidInputAddMany()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('add expects an email address or Laminas\Mail\Address object');
        $this->list->addMany([null]);
    }

    public function testGetReturnsAddressObjectWhenEmailFound()
    {
        $this->list->add('test@example.com');
        $address = $this->list->get('test@example.com');
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('test@example.com', $address->getEmail());
    }

    public function testCanAddAddressWithName()
    {
        $this->list->add('test@example.com', 'Example Test');
        $address = $this->list->get('test@example.com');
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals('test@example.com', $address->getEmail());
        $this->assertEquals('Example Test', $address->getName());
    }

    public function testCanAddManyAddressesAtOnce()
    {
        $addresses = [
            'test@example.com',
            'list@example.com' => 'Example List',
            new Address('announce@example.com', 'Announce List'),
        ];
        $this->list->addMany($addresses);
        $this->assertEquals(3, count($this->list));
        $this->assertTrue($this->list->has('test@example.com'));
        $this->assertTrue($this->list->has('list@example.com'));
        $this->assertTrue($this->list->has('announce@example.com'));
    }

    public function testLosesParensInName()
    {
        $header = '"Supports (E-mail)" <support@example.org>';

        $to = Header\To::fromString('To:' . $header);
        $addressList = $to->getAddressList();
        $address = $addressList->get('support@example.org');
        $this->assertEquals('Supports', $address->getName());
        $this->assertEquals('E-mail', $address->getComment());
        $this->assertEquals('support@example.org', $address->getEmail());
    }

    public function testDoesNotStoreDuplicatesAndFirstWins()
    {
        $addresses = [
            'test@example.com',
            new Address('test@example.com', 'Example Test'),
        ];
        $this->list->addMany($addresses);
        $this->assertEquals(1, count($this->list));
        $this->assertTrue($this->list->has('test@example.com'));
        $address = $this->list->get('test@example.com');
        $this->assertNull($address->getName());
    }

    /**
     * Microsoft Outlook sends emails with semicolon separated To addresses.
     *
     * @see https://blogs.msdn.microsoft.com/oldnewthing/20150119-00/?p=44883
     */
    public function testSemicolonSeparator()
    {
        $header = 'Some User <some.user@example.com>; uzer2.surname@example.org;'
            . ' asda.fasd@example.net, root@example.org';

        // In previous versions, this throws: 'The input exceeds the allowed
        // length'; hence the try/catch block, to allow finding the root cause.
        try {
            $to = Header\To::fromString('To:' . $header);
        } catch (InvalidArgumentException $e) {
            $this->fail('Header\To::fromString should not throw');
        }
        $addressList = $to->getAddressList();

        $this->assertEquals('Some User', $addressList->get('some.user@example.com')->getName());
        $this->assertTrue($addressList->has('uzer2.surname@example.org'));
        $this->assertTrue($addressList->has('asda.fasd@example.net'));
        $this->assertTrue($addressList->has('root@example.org'));
    }

    public function testMergeTwoLists()
    {
        $otherList = new AddressList();
        $this->list->add('one@example.net');
        $otherList->add('two@example.org');
        $this->list->merge($otherList);
        $this->assertEquals(2, count($this->list));
    }

    public function testDeleteSuccess()
    {
        $this->list->add('test@example.com');
        $this->assertTrue($this->list->delete('test@example.com'));
        $this->assertEquals(0, count($this->list));
    }

    public function testDeleteNotExist()
    {
        $this->assertFalse($this->list->delete('test@example.com'));
    }

    public function testKey()
    {
        $this->assertNull($this->list->key());
        $this->list->add('test@example.com');
        $this->list->add('test@example.net');
        $this->list->add('test@example.org');
        $this->list->rewind();
        $this->assertSame('test@example.com', $this->list->key());
        $this->list->next();
        $this->assertSame('test@example.net', $this->list->key());
        $this->list->next();
        $this->assertSame('test@example.org', $this->list->key());
    }

    /**
     * If name-field is quoted with "", then ' inside it should not treated as terminator, but as value.
     */
    public function testMixedQuotesInName()
    {
        $header = '"Bob O\'Reilly" <bob@example.com>,blah@example.com';

        // In previous versions, this throws:
        // 'Bob O'Reilly <bob@example.com>,blah' can not be matched against dot-atom format
        // hence the try/catch block, to allow finding the root cause.
        try {
            $to = Header\To::fromString('To:' . $header);
        } catch (InvalidArgumentException $e) {
            $this->fail('Header\To::fromString should not throw');
        }

        $addressList = $to->getAddressList();
        $this->assertTrue($addressList->has('bob@example.com'));
        $this->assertTrue($addressList->has('blah@example.com'));
        $this->assertEquals("Bob O'Reilly", $addressList->get('bob@example.com')->getName());
    }
}
