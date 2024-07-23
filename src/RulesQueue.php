<?php

namespace Jeyserver\BotBlocker;

use SplQueue;

/**
 * @method public enqueue(IRule $void): void;
 *
 * @extends SplQueue<IRule>
 */
class RulesQueue extends SplQueue
{
}
