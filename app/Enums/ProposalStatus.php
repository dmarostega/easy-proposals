<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case Draft = 'rascunho';
    case Sent = 'enviada';
    case Viewed = 'visualizada';
    case Approved = 'aprovada';
    case Rejected = 'recusada';
    case Expired = 'expirada';
}
