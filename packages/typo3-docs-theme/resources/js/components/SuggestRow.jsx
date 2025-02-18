import React, { forwardRef } from 'react';

const Icon = ({ type }) => {
    switch (type) {
        case 'search': return <i className="fa fa-search" />;
        case 'file': return <i className="fa-regular fa-file-code" />;
        default: return null;
    }
};

const ScopeContent = ({ scopes, title, type, packageName }) => {
    if (scopes?.length > 0) {
        return (
            <>
                <div className='suggest-row__scope'>
                    {scopes.map(({ title: scopeName, type }) => (
                        <>
                            <p className="suggest-row__scope-type">{type && `${type}:`}</p>
                            {scopeName && <p className="suggest-row__scope-name">{scopeName}</p>}
                        </>
                    ))}
                    <p className="suggest-row__title">{title}</p>
                </div>
            </>
        );
    }

    return (
        <div className='suggest-row__scope' title={`${title}${packageName ? ` (${packageName})` : ''}`}>
            <p className="suggest-row__scope-type">{type && `${type}:`}</p>
            <p className="suggest-row__title" dangerouslySetInnerHTML={{ __html: title }} />
            {packageName && <p className="suggest-row__description">({packageName})</p>}
        </div>
    );
};

const SuggestRow = forwardRef(({
    title,
    packageName,
    scopes,
    tooltip,
    onClick,
    type,
    href,
    isActive,
    icon = 'search'
}, ref) => {
    const handleOnClick = (e) => {
        if (!href) {
            e.preventDefault();
            onClick?.();
        }
    };

    return (
        <a
            onClick={handleOnClick}
            ref={ref}
            href={href}
            className={`suggest-row ${isActive ? 'suggest-row--active' : ''}`}
        >
            <div className="suggest-row__icon">
                <Icon type={icon} />
            </div>
            <div className="suggest-row__content">
                <ScopeContent
                    scopes={scopes}
                    title={title}
                    type={type}
                    packageName={packageName}
                />
            </div>
            {tooltip && <p className="suggest-row__tooltip">{tooltip}</p>}
        </a>
    );
});

SuggestRow.displayName = 'SuggestRow';

export default SuggestRow;
