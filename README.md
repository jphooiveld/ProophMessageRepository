# EventSauce Prooph Message Repository
[![Build Status](https://travis-ci.com/jphooiveld/ProophMessageRepository.svg?branch=master)](https://travis-ci.com/jphooiveld/ProophMessageRepository)
## Info

This bundle integrates the [Prooph Event Store](http://getprooph.org/docs/html/event-store/event_store.html) 
as a message repository for [EventSauce](https://eventsauce.io/).

Whatever prooph event store implementation you want to use is up to yourself. 

It is also revised to read up on both EventSauce and Prooph documentation to make certain
both use the strategy (single stream or one stream per aggregate), or you might run into errors.

This library is inspired by the [Doctrine Message Repository](https://packagist.org/packages/eventsauce/doctrine-message-repository) 
for EventSauce by Frank de Jonge and borrows the same concept and structure for the tests. 

The event store you decide to use **must** use the **SerializablePayloadMessageFactory** class if it requires a message factory.

# License

This bundle is under the MIT license. See the complete license [in the code](LICENSE).
