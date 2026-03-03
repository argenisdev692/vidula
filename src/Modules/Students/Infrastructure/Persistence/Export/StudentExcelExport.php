<?php
declare(strict_types=1);
namespace Modules\Students\Infrastructure\Persistence\Export;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Students\Application\Queries\ListStudent\ListStudentHandler;
use Modules\Students\Application\Queries\ListStudent\ListStudentQuery;

final class StudentExcelExport implements FromView
{
    public function __construct(
        private readonly ListStudentHandler $handler,
        private readonly ListStudentQuery $query
    ) {
    }

    public function view(): View
    {
        return view('exports.pdf.students', ['items' => $this->handler->handle($this->query)['data']]);
    }
}
