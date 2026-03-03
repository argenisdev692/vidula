<?php
declare(strict_types=1);
namespace Modules\Students\Infrastructure\Persistence\Export;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Students\Application\Queries\ListStudent\ListStudentHandler;
use Modules\Students\Application\Queries\ListStudent\ListStudentQuery;

final class StudentPdfExport
{
    public function __construct(
        private readonly ListStudentHandler $handler,
        private readonly ListStudentQuery $query
    ) {
    }

    public function export(): mixed
    {
        $pdf = Pdf::loadView('exports.pdf.students', ['items' => $this->handler->handle($this->query)['data']]);
        return $pdf->download('students.pdf');
    }
}
