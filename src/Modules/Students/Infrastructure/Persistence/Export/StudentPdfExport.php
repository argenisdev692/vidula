<?php
declare(strict_types=1);
namespace Modules\Student\Infrastructure\Persistence\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Student\Application\Queries\ListStudent\ListStudentHandler;
use Modules\Student\Application\Queries\ListStudent\ListStudentQuery;

final class StudentPdfExport
{
    public function __construct(
        private readonly ListStudentHandler $handler,
        private readonly ListStudentQuery $query
    ) {}

    public function export(): mixed
    {
        $pdf = Pdf::loadView('exports.students', ['items' => $this->handler->handle($this->query)['data']]);
        return $pdf->download('students.pdf');
    }
}
